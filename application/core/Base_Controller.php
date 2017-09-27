<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

use Iapps\Common\Helper\ResponseMessage;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Helper\InputValidator;

use Iapps\RemittanceService\Common\RemittanceMessageCommonRepository;
use Iapps\Common\MessageCommon\MessageCommonService;

use Iapps\Common\SystemCode\SystemCodeRepository;
use Iapps\Common\SystemCode\SystemCodeService;

use Iapps\Common\CoreConfigData\CoreConfigDataRepository;
use Iapps\Common\CoreConfigData\CoreConfigDataService;

use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\AuditLog\AuditLogRepository;
use Iapps\Common\AuditLog\AuditLogService;
use Iapps\Common\Microservice\AccountService\AccountService;

class Base_Controller extends CI_Controller
{
    protected $lang;
    protected $channel;
    protected $clientToken;
    protected $_serv;
    protected $_service_audit_log;

    public function __construct()
    {
        parent::__construct();

        //initiate as 500
        http_response_code(500);
        date_default_timezone_set('UTC');


        $this->response_message = new ResponseMessage();
        
        $this->load->model('common/Audit_log_model');
        $repo = new AuditLogRepository($this->Audit_log_model);
        $this->_service_audit_log = new AuditLogService($repo);
    }

    protected function is_required($param = NULL, $rules = NULL, $checkZero = TRUE) {
        $validator = InputValidator::make($param, $rules, $checkZero);

        if( $validator->fails() )
        {
            $this->_response($validator->getErrorResponse());
            return false;
        }

        return true;
    }

    protected function _response(ResponseMessage $response)
    {
        $this->response_message = $response;
        $this->set_output();
    }

    protected function _respond($status_code = ResponseHeader::HEADER_SUCCESS)
    {
        $this->response_message->getHeader()->setStatus($status_code);

        $this->set_output();
    }

    protected function set_output()
    {
        $this->response_message->getHeader()->setField(ResponseHeader::FIELD_CONTENT_TYPE, ResponseHeader::VALUE_JSON);
        $this->response_message->getHeader()->setField(ResponseHeader::FIELD_CACHE_CONTROL, 'no-store');

        $this->output->set_status_header($this->response_message->getHeader()->getStatus() );
        foreach($this->response_message->getHeader() AS $fieldvalue)
        {
            $this->output->set_header($fieldvalue);
        }

        $this->output->set_output($this->response_message->getJsonMessage());
    }

    protected function _respondWithCode($code, $status_code = ResponseHeader::HEADER_SUCCESS, $result=NULL, $lang=NULL, $preMessage = NULL)
    {
        if( $code != NULL )
        {
            $this->response_message->setStatusCode($code);
            if( $preMessage )
                $message = $preMessage;
            else
                $message = $this->_getMessageByCode($code, $lang);
            $this->response_message->setMessage($message, $result);

            $this->_respond($status_code);
        }
    }

    protected function _respondWithSuccessCode($code, $result=NULL, $lang=NULL, $preMessage = NULL)
    {
    	$this->_respondWithCode($code, ResponseHeader::HEADER_SUCCESS, $result, $lang, $preMessage);        
    }

    /*
     * call this function will immediately display output and terminate
     */
    protected function _respondAndTerminate()
    {
        $this->set_output();
        $this->output->_display();
        //todo better way to terminate instead of die?
        die();
    }

    protected function _getMessageByCode($code, $lang=NULL)
    {
        if( $lang == NULL )
            $lang = $this->_getLang();

        $this->load->model('common/Message_common_model');
        $repo = new RemittanceMessageCommonRepository($this->Message_common_model);
        $serv = new MessageCommonService($repo);

        return $serv->getMessage($code, $lang);
    }

    protected function _getSystemCodeByCode($code, $group)
    {
        $this->load->model('common/Systemcode_model');
        $repo = new SystemCodeRepository($this->Systemcode_model);
        $serv = new SystemCodeService($repo);

        return $serv->getByCode($code, $group);
    }

    protected function _getCoreConfigByCode($code)
    {
        $this->load->model('common/Core_config_data_model');
        $repo = new CoreConfigDataRepository($this->Core_config_data_model);
        $serv = new CoreConfigDataService($repo);

        return $serv->getConfig($code);
    }

    protected function _get_admin_id($function = NULL, $access_type = NULL)
    {
        return $this->input->post('admin_id');
    }

    protected function _getUserProfileId($function = NULL, $access_type = NULL, $session_type = NULL)
    {
        $accessToken = $this->input->get_request_header(ResponseHeader::FIELD_X_AUTHORIZATION);

        $account_serv = AccountServiceFactory::build();
        if( $user_profile_id = $account_serv->checkAccess($accessToken, $function, $access_type, $session_type) )
        {
            return $user_profile_id;
        }

        $this->response_message->getHeader()->setStatus(ResponseHeader::HEADER_UNAUTHORIZED);
        $this->response_message->setStatusCode(ResponseHeader::HEADER_UNAUTHORIZED);
        $this->response_message->setMessage('Invalid oauth token credentials.');

        if( $response = $account_serv->getLastResponse() AND isset($response['status_code']) )
        {
            if( $response['status_code'] == AccountService::CODE_INVALID_ACCESS_TOKEN )
            {
                $this->response_message->getHeader()->setStatus(ResponseHeader::HEADER_UNAUTHORIZED);
                $this->response_message->setStatusCode(ResponseHeader::HEADER_UNAUTHORIZED);
                $this->response_message->setMessage('Invalid oauth token credentials.');
            }
            else
            {
                $this->_respondWithCode($response['status_code'], ResponseHeader::HEADER_FORBIDDEN);
            }
        }

        $this->set_output();
        return false;
    }

    protected function _getLang()
    {
        $this->lang = NULL;
        if( $lang = $this->input->get_request_header(ResponseHeader::FIELD_X_LANGUAGE) )
            $this->lang = $lang;

        return $this->lang;
    }

    protected function _getIpAddress()
    {
        return $this->input->ip_address();
    }

    protected function _getChannel()
    {
        return $this->channel;
    }

    protected function _getLimit()
    {
        $limit = 0;
        if( $this->input->post('limit') )
            $limit = $this->input->post('limit');
        elseif( $this->input->get('limit') )
            $limit = $this->input->get('limit');

        if( is_numeric($limit) )
        {
            if( $limit > 0 )
                return $limit;
        }

        return DEFAULT_LIMIT;
    }

    protected function _getPage()
    {
        $page = 0;
        if( $this->input->post('page') )
            $page = $this->input->post('page');
        elseif( $this->input->get('page') )
            $page = $this->input->get('page');

        if( is_numeric($page) )
        {
            if( $page > 0 )
                return $page;
        }

        return DEFAULT_PAGE;
    }

    protected function _authoriseClient()
    {
        $app_id = $this->input->get_request_header(ResponseHeader::FIELD_X_APP);
        $version = $this->input->get_request_header(ResponseHeader::FIELD_X_VERSION);

        $account_serv = AccountServiceFactory::build();

        list($success, $info, $clientToken) = $account_serv->authorizeClient($app_id, $version);

        if( $success )
        {
            $this->channel = $info;
            $this->clientToken = $clientToken;
            return true;
        }
        else
        {
            $this->response_message = $info;
            $this->_respondAndTerminate();
            return false;
        }
    }

    protected function convertStringToBooleanInt($value)
    {
        if($value === 'true' OR $value === true)
            return 1;
        else
            return 0;
    }
    
    public function getAuditLog()
    {
        if( $this->is_required($this->input->get(), array("id")) == false)
            return false;
                
        $id = $this->input->get("id");
        $tableName = $this->_service_audit_log->getTableName();
        
        if( $object = $this->_service_audit_log->getAllLogById($tableName, $id) )
        {
            //todo Pagination
            $this->_respondWithSuccessCode($this->_service_audit_log->getResponseCode(), array('result' => $object->result, 'total' => $object->total));
            return true;
        }

        $this->_respondWithCode($this->_service_audit_log->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
    protected function uploadToServerLocal($file = '')
    {
        if (isset($_FILES[$file])) {
            $name      = time();

            $path = './upload/document/';

            if(!is_dir($path)) //create the folder if it's not already exists
            {
                mkdir($path,0755,TRUE);
            }

            $config['file_name']     = mt_rand().$name;
            $config['upload_path']   = $path;
            $config['overwrite']     = TRUE;
            $config['allowed_types'] = 'gif|jpg|png|csv|xls|xlsx';
            $config['max_size']      = '2000';
            $config['max_width']     = '2000';
            $config['max_height']    = '2000';

            $this->load->library('upload', $config);

            if ( ! $this->upload->do_upload($file)) {
                $error = array('error' => $this->upload->display_errors('', ''));
                return $error;
            } else {
                return $this->upload->data();
            }
        }
        return false;
    }

    public function export_report_pdf ($results,$report_type)
    {
        $fileName = $report_type.'.pdf';

        $filepath = "./upload/" . $fileName;
        
        $result['result'] = $results[0];
        $this->generate_html_pdf_receive($result, $report_type.'.php', $filepath);

        return $filepath;
    }

    public function generate_html_pdf_receive($params, $viewFileName, $filepath)
    {
        ini_set('memory_limit', '-1');      
        ini_set('max_input_time', '-1');      
        ini_set('max_execution_time', '-1'); 

        $dompdf = new Dompdf\Dompdf();
        $html = $this->load->view($viewFileName, $params, true);
        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'landscape');
        // Render the HTML as PDF
        $dompdf->render();
        // Output the generated PDF to Browser
        $pdf = $dompdf->output($filepath);
        @file_put_contents($filepath, $pdf);

        // $dompdf->stream($pdf);
    }


    protected function _respondWithSuccessCodeAndCustomMessage($code,$message, $result)
    {
        if($code != NULL)
        {
            $this->response_message->setStatusCode($code);
            $this->response_message->setMessage($message,$result);
            $this->_respond();
        }
    }

    protected function _returnObsoleteFunction()
    {
        $this->response_message->getHeader()->setStatus(ResponseHeader::HEADER_NOT_FOUND);
        $this->response_message->setStatusCode(ResponseHeader::HEADER_NOT_FOUND);
        $this->response_message->setMessage('Obsolete function');
        $this->set_output();
        return false;
    }
}

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">

<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
    <title>
        Modern
    </title>
    <style>
    .tb_header{
        text-align:center;
        vertical-align: bottom;  font-size: 9pt;
        border:1px #000 solid;
    }
    </style>
</head>

<body style="margin: 0; padding: 0; background: #ffffff;" bgcolor="#ffffff">

    <div align="center" style="font-size: 20px;">
        FROM <?php echo $result['partner_name'];?>
        <br>
        FUNDS ACCEPTED SUMMARY
    </div>

    <div>&nbsp;</div>
    <div>From : <?php echo ($result['start_time'].' To: '. $result['end_time']);?></div>
    <div>&nbsp;</div>

    <table align="center" border="1" width="900px" style="font-size:8px;">
          <tr style="background-color:#ABA8A8">
            <td style="width:300px">Remitter Type</td>           
            <td style="width:300px">No.of Remittance</td>
            <td style="width:300px">Amount(SGD)</td>
          </tr>          
       
          <tr style="background-color:#ABA8A8">
              <td style="width:300px">NON RESIDENT</td>                
              <td style="width:300px"></td>
              <td style="width:300px"></td>
          </tr>   

          <tr>   
              <td style="width:300px">Individual</td>
              <td style="width:300px"><?php echo $result['non_resident']['outward']['individual_no_of_remittance'];?></td>
              <td style="width:300px"><?php echo $result['non_resident']['outward']['individual_amount'];?></td>
          </tr>     

          <tr>   
              <td style="width:300px">Company</td>
              <td style="width:300px"><?php echo $result['non_resident']['outward']['company_no_of_remittance'];?></td>
              <td style="width:300px"><?php echo $result['non_resident']['outward']['company_amount'];?></td>
          </tr>     

          <tr>   
              <td style="width:300px">Total Outward</td>
              <td style="width:300px"><?php echo $result['non_resident']['total_outward_no_of_remittance'];?></td>
              <td style="width:300px"><?php echo $result['non_resident']['total_outward_amount'];?></td>       
          </tr>      
        
          <tr>   
              <td style="width:300px">Individual</td>
              <td style="width:300px"><?php echo $result['non_resident']['inward']['individual_no_of_remittance'];?></td>
              <td style="width:300px"><?php echo $result['non_resident']['inward']['individual_amount'];?></td>
          </tr>     

          <tr>   
              <td style="width:300px">Company</td>
              <td style="width:300px"><?php echo $result['non_resident']['inward']['company_no_of_remittance'];?></td>
              <td style="width:300px"><?php echo $result['non_resident']['inward']['company_amount'];?></td>
          </tr>   

          <tr>   
              <td style="width:300px">Total Inward</td>
              <td style="width:300px"><?php echo $result['non_resident']['total_inward_no_of_remittance'];?></td>
              <td style="width:300px"><?php echo $result['non_resident']['total_inward_amount'];?></td>       
          </tr>  

          <tr>   
              <td style="width:300px">Total NON-RESIDENT</td>
              <td style="width:300px"><?php echo $result['non_resident']['total_inward_no_of_remittance'] + $result['non_resident']['total_outward_no_of_remittance'];?></td>
              <td style="width:300px"><?php echo $result['non_resident']['total_inward_amount'] + $result['non_resident']['total_outward_amount'];?></td>       
          </tr>  

          <tr style="background-color:#ABA8A8">
              <td style="width:300px">RESIDENT</td>                
              <td style="width:300px"></td>
              <td style="width:300px"></td>
          </tr>   

          <tr>   
              <td style="width:300px">Individual</td>
              <td style="width:300px"><?php echo $result['resident']['outward']['individual_no_of_remittance'];?></td>
              <td style="width:300px"><?php echo $result['resident']['outward']['individual_amount'];?></td>
          </tr>     

          <tr>   
              <td style="width:300px">Company</td>
              <td style="width:300px"><?php echo $result['resident']['outward']['company_no_of_remittance'];?></td>
              <td style="width:300px"><?php echo $result['resident']['outward']['company_amount'];?></td>
          </tr>     

          <tr>   
              <td style="width:300px">Total Outward</td>
              <td style="width:300px"><?php echo $result['resident']['total_outward_no_of_remittance'];?></td>
              <td style="width:300px"><?php echo $result['resident']['total_outward_amount'];?></td>       
          </tr>   

          <tr>   
              <td style="width:300px">Individual</td>
              <td style="width:300px"><?php echo $result['resident']['inward']['individual_no_of_remittance'];?></td>
              <td style="width:300px"><?php echo $result['resident']['inward']['individual_amount'];?></td>
          </tr>     

          <tr>   
              <td style="width:300px">Company</td>
              <td style="width:300px"><?php echo $result['resident']['inward']['company_no_of_remittance'];?></td>
              <td style="width:300px"><?php echo $result['resident']['inward']['company_amount'];?></td>
          </tr>   

          <tr>   
              <td style="width:300px">Total Inward</td>
              <td style="width:300px"><?php echo $result['resident']['total_inward_no_of_remittance'];?></td>
              <td style="width:300px"><?php echo $result['resident']['total_inward_amount'];?></td>       
          </tr>  

          <tr>   
              <td style="width:300px">Total RESIDENT</td>
              <td style="width:300px"><?php echo $result['resident']['total_inward_no_of_remittance'] + $result['resident']['total_outward_no_of_remittance'];?></td>
              <td style="width:300px"><?php echo $result['resident']['total_inward_amount'] + $result['resident']['total_outward_amount'];?></td>       
          </tr>                   
      </table>

    <div>&nbsp;</div>
    <div>&nbsp;</div>
    <div>Generated by SLIDE System on : <?php echo date('d/m/Y h:i:s a', time());?></div>

</body>

</html>

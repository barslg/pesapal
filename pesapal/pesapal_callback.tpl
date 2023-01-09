<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>{$pagetitle} - {$companyname}</title>

    <link href="templates/{$template}/css/bootstrap.css" rel="stylesheet">
    <link href="templates/{$template}/css/whmcs.css" rel="stylesheet">

  </head>

  <body  class="popupwindow">

    <h2>Payment Details</h2><br />
    
    <div class="row-fluid">
      <div class="span6">
        <table class="table table-striped table-bordered">
          <tr><td><strong>Invoice No: </strong></td> <td>{$invoiceid}</td></tr>
          <tr><td><strong>Transaction Id: </strong></td> <td>{$pesapalTrackingId}</td></tr>
          <tr><td><strong>Payment Status: </strong></td> <td>{$status}</td></tr>
        </table>
      </div>
      <div class="span6">
        {if $status == "PENDING" || $status == "INVALID" }
          <div class="alert alert-info">
          <strong>Information: </strong>Thank you for using our system. Your payment was processed successfully but is still waiting confirmation on its status.<br /><br />
          You will be notified once we confirm this.
          </div>
        {elseif $status == "FAILED"}
          <div class="alert alert-danger">
          <strong>Error: </strong>Thank you for using our system. However, your payment failed and no deductions were made on your account. Please contact PesaPal support for more information.<br /><br />
          Click <a href="http://support.pesapal.com" target="_blank">here</a> to open a support ticket at PesaPal.
          </div>
        {/if}
      </div>
    </div>
  </body>
</html>
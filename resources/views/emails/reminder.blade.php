<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
</head>

<body style="border:2px solid #494949;  margin:7px;">
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td align="center" valign="middle">@include('emails.header')</td>
  </tr>
  <tr>
    <td height="2" align="center" valign="top" bgcolor="#494949"><div></div></td>
  </tr>
  <tr>
    <td align="center" valign="top">&nbsp;</td>
  </tr>
  <tr>
    <td align="center" valign="top" style="padding:0 20px;">
    Assalam Alaykom,

<p>This is a gentle reminder to renew the payment fees for {{$name}}'s Qur'an sessions with Nour Academy.
</p>

<p>Number of due cycles: {{$due_cycles}}</p>

<p>Total amount due is: {{$due_amount}}</p>

<p>(Cycle = {{$cycles}} classes).</p>

<p>
We would really appreciate if you submit your due payments before next week to the following paypal account: payments@nouracademy.com 
</p>



<p>If you have any concerns please feel free to contact us on our email: payments@nouracademy.com</p>

<p>Your cooperation is highly appreciated.</p>


<p>Best Regards,</p>

<p>Ahmad Aly</p>

<p>Payments Team</p>
        
    
    
    
    </td>
  </tr>
  <tr>
    <td align="center" valign="top">&nbsp;</td>
  </tr>
  <tr>
    <td height="2" align="center" valign="top" bgcolor="#494949"><div></div></td>
  </tr>
  <tr>
    <td align="center">@include('emails.footer')</td>
  </tr>
</table>
</body>
</html>
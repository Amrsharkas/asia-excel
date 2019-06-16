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
    <h3>Name: {{$candidate->name}}</h3>
    
    @if($candidate->user_phase == "phoneInterview")
    	<p>Congratulations you have passed the Islamic knowledge test. </p><p>In the next phase a member of our team will contact you via phone to conduct a quick interview to assess your level of English fluency. </p><p>Thank you for your patience and may Allah reward your efforts.</p>
    @endif
    @if($candidate->user_phase == "pilotTest")
<p>Congratulations you have passed the phone interview phase. Now you are just one step away from becoming a volunteer teacher at Nour Academy. </p><p>In this phase you have access to our training material and course content which you can view by clicking the respective tabs on the left which you can view by accessing the portal by <a href="http://volunteers.nouracademy.com/">clicking here</a>.  </p>
<p>and login with the following credintials</p>
        <p><strong>Email</strong>: {{$candidate->email}}</p>
        <p><strong>Password</strong>: {{$candidate->dec_password}}</p>
<p>The training material covers how to use our course material and our virtual classrooms. Please review the training material thoroughly. This should take no longer than a couple of hours. Afterwards take a quick look at the course material tab for examples ( <span style='font-weight:bold;color:red; text-tranform:uppercase;'>We have included all our course content for reference only, you only need to review a few samples</span> )</p><p>When you feel you are comfortable understanding how to use our course material and virtual classroom please click on the tab [Ready for pilot test] on the left bar and click yes. After which a member of our team will get in touch with you to determine a date and time when we can conduct a safe pilot test where you will act as a teacher and someone from our team will act as a student. </p><p>This is the last phase in screening to make sure that you are ready to start providing lessons to non-Arabic speaking students.</p><p>Thank you for your patience and may Allah reward your efforts.</p>    @endif
    @if($candidate->user_phase == "passedAll")
    	<p>Congratulations! you are currently a volunteer Islamic practices teacher at Nour Academy. May Allah bless your efforts and intentions.</p><p>Our registration team should contact you to check for your availability whenever a new student group is ready to take a course.</p>
        <p>Please visit the following link to view the course content and training material whenever you need to review them. </p>
    	<p><a style="color:#494949; font-weight:bold;" href="http://volunteers.nouracademy.com">Click here</a></p>
        <p>and login with the following credintials</p>
        <p><strong>Email</strong>: {{$candidate->email}}</p>
        <p><strong>Password</strong>: {{$candidate->dec_password}}</p>
    @endif
    
    
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
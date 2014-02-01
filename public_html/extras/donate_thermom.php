<?php
$pledge_goal = 5000;
$pledges_received = 125;
$pledge_percentage = round(($pledges_received *100)/$pledge_goal );
?>
<h3>Open-Source Fundraiser</h3>
<table>
<tr><td>
<div class="thermoouter" style="position:relative; width:40px;height:73px; padding:1px; border: 1px solid black; border-radius: 4px;">
        <div class="thermoinner" style="position:absolute; bottom:0; width:40px; background-color: #F00; border-bottom-right-radius:4px; border-bottom-left-radius:4px; height:<?php echo $pledge_percentage ; ?>%;"></div>
</div></td>
<td valign=top>
$<?php echo $pledge_goal; ?> - We go open-source!<br>
$3000 - Do stuff<br>
$500 - Mini Goal <br>
$200 - do<br>
</td></tr>
</table>
<?php echo $pledge_percentage ; ?>% of the goal.

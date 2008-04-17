<ul class="stepIndicator current-$CurrentStep.class">
<% control AllStepsLinear %>
	<li class="$ClassName $LinkingMode<% if FirstLast %> $FirstLast<% end_if %>">
		<% if LinkingMode = current %><% else %><% if ID %><a href="{$Top.URLSegment}/?MultiFormSessionID={$SessionID}&StepID={$ID}"><% end_if %><% end_if %>
		<% if Title %>$Title<% else %>$ClassName<% end_if %>
		<% if LinkingMode = current %><% else %><% if ID %></a><% end_if %><% end_if %>
	</li>
<% end_control %>
</ul>
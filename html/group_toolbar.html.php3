<?php
print <<<EOM
<tr valign=top>
<td align=center bgcolor="black" width=100>
<a href="group_admin.php3?login=$login" title="Administer Group"><font color="white"><b>ADMIN</b></font></a></td>
<td align=center bgcolor="black" width=100>
<a href="user_edit.php3?login=$login&user_type=group" title="Edit Group Dialup Settings"><font color="white"><b>EDIT</b></font></a></td>
<td align=center bgcolor="black" width=100>
<a href="user_delete.php3?login=$login&user_type=group" title="Delete Group"><font color="white"><b>DELETE</b></font></a></td>
</tr>
EOM;
?>

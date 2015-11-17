<?php
/*
  +-------------------------------------------------------------------------+
  | Copyright (C) 2004-2015 The Cacti Group                                 |
  |                                                                         |
  | This program is free software; you can redistribute it and/or           |
  | modify it under the terms of the GNU General Public License             |
  | as published by the Free Software Foundation; either version 2          |
  | of the License, or (at your option) any later version.                  |
  |                                                                         |
  | This program is distributed in the hope that it will be useful,         |
  | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
  | GNU General Public License for more details.                            |
  +-------------------------------------------------------------------------+
  | Cacti: The Complete RRDTool-based Graphing Solution                     |
  +-------------------------------------------------------------------------+
  | This code is designed, written, and maintained by the Cacti Group. See  |
  | about.php and/or the AUTHORS file for specific developer information.   |
  +-------------------------------------------------------------------------+
  | http://www.cacti.net/                                                   |
  +-------------------------------------------------------------------------+
 */

include("./include/auth.php");
include("./include/top_header.php");

api_plugin_hook('console_before');
?>
<table width="100%" align="center">
	<tr>
		<td class="textAreaNotes">
			<strong>You are now logged into <a href="about.php">Cacti</a>. You can follow these basic steps to get
				started.</strong>

			<ul>
				<li><a href="host.php">Create devices</a> for network</li>
				<li><a href="graphs_new.php">Create graphs</a> for your new devices</li>
				<li><a href="graph_view.php">View</a> your new graphs</li>
			</ul>
		</td>
		<td class="textAreaNotes" align="right" valign="top">
			<strong>Version <?php print $config["cacti_version"]; ?></strong>
		</td>
	</tr>
</table>

<table id="test1" class="display" cellspacing="0" width="100%">
	<thead>
		<tr>
			<th>Name</th>
			<th>Position</th>
			<th>Office</th>
			<th>Age</th>
			<th>Salary</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th>Name</th>
			<th>Position</th>
			<th>Office</th>
			<th>Age</th>
			<th>Salary</th>
		</tr>
	</tfoot>
	<tbody>
		<tr>
			<td>Tiger Nixon</td>
			<td>System Architect</td>
			<td>Edinburgh</td>
			<td>61</td>
			<td>$320,800</td>
		</tr>
		<tr>
			<td>Cedric Kelly</td>
			<td>Senior Javascript Developer</td>
			<td>Edinburgh</td>
			<td>22</td>
			<td>$433,060</td>
		</tr>
		<tr>
			<td>Sonya Frost</td>
			<td>Software Engineer</td>
			<td>Edinburgh</td>
			<td>23</td>
			<td>$103,600</td>
		</tr>
		<tr>
			<td>Quinn Flynn</td>
			<td>Support Lead</td>
			<td>Edinburgh</td>
			<td>22</td>
			<td>$342,000</td>
		</tr>
		<tr>
			<td>Dai Rios</td>
			<td>Personnel Lead</td>
			<td>Edinburgh</td>
			<td>35</td>
			<td>$217,500</td>
		</tr>
		<tr>
			<td>Gavin Joyce</td>
			<td>Developer</td>
			<td>Edinburgh</td>
			<td>42</td>
			<td>$92,575</td>
		</tr>
		<tr>
			<td>Martena Mccray</td>
			<td>Post-Sales support</td>
			<td>Edinburgh</td>
			<td>46</td>
			<td>$324,050</td>
		</tr>
		<tr>
			<td>Jennifer Acosta</td>
			<td>Junior Javascript Developer</td>
			<td>Edinburgh</td>
			<td>43</td>
			<td>$75,650</td>
		</tr>
		<tr>
			<td>Shad Decker</td>
			<td>Regional Director</td>
			<td>Edinburgh</td>
			<td>51</td>
			<td>$183,000</td>
		</tr>
	</tbody>
</table>
<!--<table id="test2" class="display" cellspacing="0" width="100%">
	<thead>
		<tr>
			<th>Name</th>
			<th>Position</th>
			<th>Office</th>
			<th>Age</th>
			<th>Salary</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th>Name</th>
			<th>Position</th>
			<th>Office</th>
			<th>Age</th>
			<th>Salary</th>
		</tr>
	</tfoot>
	<tbody>
		<tr>
			<td>Jena Gaines</td>
			<td>Office Manager</td>
			<td>London</td>
			<td>30</td>
			<td>$90,560</td>
		</tr>
		<tr>
			<td>Haley Kennedy</td>
			<td>Senior Marketing Designer</td>
			<td>London</td>
			<td>43</td>
			<td>$313,500</td>
		</tr>
		<tr>
			<td>Tatyana Fitzpatrick</td>
			<td>Regional Director</td>
			<td>London</td>
			<td>19</td>
			<td>$385,750</td>
		</tr>
		<tr>
			<td>Michael Silva</td>
			<td>Marketing Designer</td>
			<td>London</td>
			<td>66</td>
			<td>$198,500</td>
		</tr>
		<tr>
			<td>Bradley Greer</td>
			<td>Software Engineer</td>
			<td>London</td>
			<td>41</td>
			<td>$132,000</td>
		</tr>
		<tr>
			<td>Angelica Ramos</td>
			<td>Chief Executive Officer (CEO)</td>
			<td>London</td>
			<td>47</td>
			<td>$1,200,000</td>
		</tr>
		<tr>
			<td>Suki Burks</td>
			<td>Developer</td>
			<td>London</td>
			<td>53</td>
			<td>$114,500</td>
		</tr>
		<tr>
			<td>Prescott Bartlett</td>
			<td>Technical Author</td>
			<td>London</td>
			<td>27</td>
			<td>$145,000</td>
		</tr>
		<tr>
			<td>Timothy Mooney</td>
			<td>Office Manager</td>
			<td>London</td>
			<td>37</td>
			<td>$136,200</td>
		</tr>
		<tr>
			<td>Bruno Nash</td>
			<td>Software Engineer</td>
			<td>London</td>
			<td>38</td>
			<td>$163,500</td>
		</tr>
		<tr>
			<td>Hermione Butler</td>
			<td>Regional Director</td>
			<td>London</td>
			<td>47</td>
			<td>$356,250</td>
		</tr>
		<tr>
			<td>Lael Greer</td>
			<td>Systems Administrator</td>
			<td>London</td>
			<td>21</td>
			<td>$103,500</td>
		</tr>
	</tbody>
</table>-->

<?php
api_plugin_hook('console_after');

include("./include/bottom_footer.php");
?>

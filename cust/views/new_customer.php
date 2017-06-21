<?php

$result = mysql_query("SELECT * FROM option_values WHERE category='referall_location'");
$REFERALL_LOC = array();
while ($row = mysql_fetch_assoc($result)) {
	if ($row["category"] == "referall_location") {
		$REFERALL_LOC[$row["id"]] = $row["value"];
	}
}
asort($REFERALL_LOC);

?><script type="text/javascript">



var xmladdcus;
var xmlvalidate;
if (window.XMLHttpRequest) {
  // IE7+, Firefox, Chrome, Opera, Safari
	xmladdcus=new XMLHttpRequest();
	xmlvalidate=new XMLHttpRequest();
} else {
  // IE6, IE5
	xmladdcus=new ActiveXObject("Microsoft.XMLHTTP");
	xmlvalidate=new ActiveXObject("Microsoft.XMLHTTP");
}
xmladdcus.onreadystatechange = function() {
	if (xmladdcus.readyState == 4 && xmladdcus.status == 200) {
	 var zl = JSON.parse(xmladdcus.responseText);
	 zl = zl.results[0];
	 var ZIP = zl.address_components[0].short_name;
	 var CITY = zl.address_components[1].long_name;
	 var STATEb = zl.address_components[2].long_name;
	 var STATEs = zl.address_components[2].short_name;
	 document.getElementById("city").value = CITY;
	}
}
xmlvalidate.onreadystatechange = validateHandler;

function validateHandler() {
	if (xmlvalidate.readyState == 4 && xmlvalidate.status == 200) {
		var data = eval("("+xmlvalidate.responseText+")");
		if (!data || !data.action) return;
		switch (data.action) {
			case "error":
				document.getElementById('v_address_display').value = 'Not Validated';
				alert(data.error);
				break;
			case "notfound":
				alert("That address was not found.");
				document.getElementById('v_address_display').value = 'INVALID';
				document.getElementById('v_address').value = 'INVALID';
				break;
			case "validated":
				var apt = '';
				var zip4 = '', no_service = '';
				if (data.address2 != '') apt = ' #'+data.address2;
				if (data.zip4 != '') zip4 = '-'+data.zip4;
				else no_service = 'NO SERVICE TO: ';
				document.getElementById('v_address_display').value = data.address + apt +"\n"+ data.city +", "+ data.state +"\n"+ data.zip + zip4;
				document.getElementById('v_address').value = no_service + data.address +", "+ data.address2 +", "+ data.city +", "+ data.state +", "+ data.zip + zip4;
				break;
		}
	}
}

function validate() {
	if (document.getElementById('address').value == '' || document.getElementById('zip').value == '') {
		alert('Address and zip code are required for address validation.');
		return;
	}

	var params = [];
	params.push("address="+encodeURIComponent(document.getElementById('address').value));
	params.push("address2="+encodeURIComponent(document.getElementById('apt').value));
	params.push("city="+encodeURIComponent(document.getElementById('city').value));
	params.push("state="+encodeURIComponent(document.getElementById('state').options[document.getElementById('state').selectedIndex].value));
	params.push("zip="+encodeURIComponent(document.getElementById('zip').value));

	document.getElementById('v_address_display').value = 'Validating...';
	xmlvalidate.abort();
	xmlvalidate.onreadystatechange = validateHandler;
	xmlvalidate.open("GET","cust/ajax.php?cmd=validate&"+params.join('&'),true);
	xmlvalidate.send();
}

function ziplook() {
  var loc = document.getElementById("zip");
  if(loc.value.length==5||loc.value.length==loc.maxLength){
	 xmladdcus.open("GET","cust/ajax.php?cmd=zipsearch&str="+loc.value,true);
	 xmladdcus.send();
  }
}

</script><h3>Add New Customer</h3>
<form action="?module=cust&do=new" method="post">
<table border="0" cellspacing="3">
 <tr>
  <td class="heading" align="right">First Name</td>
  <td><input type="edit" name="firstname" size="20"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Last Name</td>
  <td><input type="edit" name="lastname" size="20"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Zipcode</td>
  <td><input type="edit" name="zip" id="zip" size="6" maxlength="5" onKeyUp="ziplook()" value=""></td>
 </tr>
 <tr>
  <td class="heading" align="right">Sex</td>
  <td>
   <input type="radio" name="is_male" value="1" CHECKED> Male
   <input type="radio" name="is_male" value="0"> Female
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">DOB (yyyy-mm-dd)</td>
  <td>
    <select name="dob_m">
      <option value="00">**MONTH**</option>
      <option value="01">January</option>
      <option value="02">Feburary</option>
      <option value="03">March</option>
      <option value="04">April</option>
      <option value="05">May</option>
      <option value="06">June</option>
      <option value="07">July</option>
      <option value="08">August</option>
      <option value="09">September</option>
      <option value="10">October</option>
      <option value="11">November</option>
      <option value="12">December</option>
    </select>
    <select name="dob_d">
      <option value="00">00</option>
      <option value="01">1</option>
      <option value="02">2</option>
      <option value="03">3</option>
      <option value="04">4</option>
      <option value="05">5</option>
      <option value="06">6</option>
      <option value="07">7</option>
      <option value="08">8</option>
      <option value="09">9</option>
      <option value="10">10</option>
      <option value="11">11</option>
      <option value="12">12</option>
      <option value="13">13</option>
      <option value="14">14</option>
      <option value="15">15</option>
      <option value="16">16</option>
      <option value="17">17</option>
      <option value="18">18</option>
      <option value="19">19</option>
      <option value="20">20</option>
      <option value="21">21</option>
      <option value="22">22</option>
      <option value="23">23</option>
      <option value="24">24</option>
      <option value="25">25</option>
      <option value="26">26</option>
      <option value="27">27</option>
      <option value="28">28</option>
      <option value="29">29</option>
      <option value="30">30</option>
    </select>
    <select name="dob_y">
      <option value="0000">0000</option>
      <option value="2005">2005</option>
      <option value="2004">2004</option>
      <option value="2003">2003</option>
      <option value="2002">2002</option>
      <option value="2001">2001</option>
      <option value="2000">2000</option>
      <option value="1999">1999</option>
      <option value="1998">1998</option>
      <option value="1997">1997</option>
      <option value="1996">1996</option>
      <option value="1995">1995</option>
      <option value="1994">1994</option>
      <option value="1993">1993</option>
      <option value="1992">1992</option>
      <option value="1991">1991</option>
      <option value="1990">1990</option>
      <option value="1989">1989</option>
      <option value="1988">1988</option>
      <option value="1987">1987</option>
      <option value="1986">1986</option>
      <option value="1985">1985</option>
      <option value="1984">1984</option>
      <option value="1983">1983</option>
      <option value="1982">1982</option>
      <option value="1981">1981</option>
      <option value="1980">1980</option>
      <option value="1979">1979</option>
      <option value="1978">1978</option>
      <option value="1977">1977</option>
      <option value="1976">1976</option>
      <option value="1975">1975</option>
      <option value="1974">1974</option>
      <option value="1973">1973</option>
      <option value="1972">1972</option>
      <option value="1971">1971</option>
      <option value="1970">1970</option>
      <option value="1969">1969</option>
      <option value="1968">1968</option>
      <option value="1967">1967</option>
      <option value="1966">1966</option>
      <option value="1965">1965</option>
      <option value="1964">1964</option>
      <option value="1963">1963</option>
      <option value="1962">1962</option>
      <option value="1961">1961</option>
      <option value="1960">1960</option>
      <option value="1959">1959</option>
      <option value="1958">1958</option>
      <option value="1957">1957</option>
      <option value="1956">1956</option>
      <option value="1955">1955</option>
      <option value="1954">1954</option>
      <option value="1953">1953</option>
      <option value="1952">1952</option>
      <option value="1951">1951</option>
      <option value="1950">1950</option>
      <option value="1949">1949</option>
      <option value="1948">1948</option>
      <option value="1947">1947</option>
      <option value="1946">1946</option>
      <option value="1945">1945</option>
      <option value="1944">1944</option>
      <option value="1943">1943</option>
      <option value="1942">1942</option>
      <option value="1941">1941</option>
      <option value="1940">1940</option>
      <option value="1939">1939</option>
      <option value="1938">1938</option>
      <option value="1937">1937</option>
      <option value="1936">1936</option>
      <option value="1935">1935</option>
      <option value="1934">1934</option>
      <option value="1933">1933</option>
      <option value="1932">1932</option>
      <option value="1931">1931</option>
      <option value="1930">1930</option>
      <option value="1929">1929</option>
      <option value="1928">1928</option>
      <option value="1927">1927</option>
      <option value="1926">1926</option>
      <option value="1925">1925</option>
      <option value="1924">1924</option>
      <option value="1923">1923</option>
      <option value="1922">1922</option>
      <option value="1921">1921</option>
      <option value="1920">1920</option>
      <option value="1919">1919</option>
      <option value="1918">1918</option>
      <option value="1917">1917</option>
      <option value="1916">1916</option>
      <option value="1915">1915</option>
      <option value="1914">1914</option>
      <option value="1913">1913</option>
      <option value="1912">1912</option>
      <option value="1911">1911</option>
      <option value="1910">1910</option>
      <option value="1909">1909</option>
      <option value="1908">1908</option>
      <option value="1907">1907</option>
      <option value="1906">1906</option>
      <option value="1905">1905</option>
      <option value="1904">1904</option>
      <option value="1903">1903</option>
      <option value="1902">1902</option>
      <option value="1901">1901</option>
      <option value="1900">1900</option>
    </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Company</td>
  <td><input type="edit" name="company" size="20"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Address</td>
  <td><input type="edit" id="address" name="address" size="40"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Apt/Suite #</td>
  <td><input type="edit" id="apt" name="apt" size="4"></td>
 </tr>
 <tr>
  <td class="heading" align="right">City</td>
  <td><input type="edit" name="city" id="city" size="20"></td>
 </tr>
 <tr>
  <td class="heading" align="right">State</td>
  <td>
  <select name="state" id="state">
    <option value="AL">Alabama</option>
    <option value="AK">Alaska</option>
    <option value="AZ">Arizona</option>
    <option value="AR">Arkansas</option>
    <option value="CA">California</option>
    <option value="CO">Colorado</option>
    <option value="CT">Connecticut</option>
    <option value="DE">Delaware</option>
    <option value="DC">District Of Columbia</option>
    <option value="FL">Florida</option>
    <option value="GA">Georgia</option>
    <option value="HI">Hawaii</option>
    <option value="ID">Idaho</option>
    <option value="IL">Illinois</option>
    <option value="IN">Indiana</option>
    <option value="IA">Iowa</option>
    <option value="KS">Kansas</option>
    <option value="KY">Kentucky</option>
    <option value="LA">Louisiana</option>
    <option value="ME">Maine</option>
    <option value="MD">Maryland</option>
    <option value="MA">Massachusetts</option>
    <option value="MI">Michigan</option>
    <option value="MN">Minnesota</option>
    <option value="MS">Mississippi</option>
    <option value="MO">Missouri</option>
    <option value="MT">Montana</option>
    <option value="NE">Nebraska</option>
    <option value="NV">Nevada</option>
    <option value="NH">New Hampshire</option>
    <option value="NJ">New Jersey</option>
    <option value="NM">New Mexico</option>
    <option value="NY" SELECTED="selected">New York</option>
    <option value="NC">North Carolina</option>
    <option value="ND">North Dakota</option>
    <option value="OH">Ohio</option>
    <option value="OK">Oklahoma</option>
    <option value="OR">Oregon</option>
    <option value="PA">Pennsylvania</option>
    <option value="RI">Rhode Island</option>
    <option value="SC">South Carolina</option>
    <option value="SD">South Dakota</option>
    <option value="TN">Tennessee</option>
    <option value="TX">Texas</option>
    <option value="UT">Utah</option>
    <option value="VT">Vermont</option>
    <option value="VA">Virginia</option>
    <option value="WA">Washington</option>
    <option value="WV">West Virginia</option>
    <option value="WI">Wisconsin</option>
    <option value="WY">Wyoming</option>
  </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Validated Address</td>
  <td><input type="hidden" name="v_address" id="v_address" value=""><textarea id="v_address_display" rows="2" cols="25" disabled="disabled" style="background-color:#CCCCCC;">Not Validated</textarea><br>
  <input type="button" value="Validate Address" onClick="validate();">
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Email</td>
  <td><input type="edit" name="email" size="40"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Home Phone</td>
  <td><input type="edit" name="phone_home" size="15"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Cell Phone</td>
  <td><input type="edit" name="phone_cell" size="15"></td>
 </tr>
  <td class="heading" align="right">Referral</td>
  <td>
    <select name="referral">
    <?php
      foreach ($REFERALL_LOC as $id => $dt) {
        if($id==317){
          $var = " SELECTED";
        } else {
          $var = " ";
        }
        echo "  <option value=\"".$id."\"".$var.">".$dt."</option>\n";
      }
    ?>
    </select>
  </td>
 <tr>
 </tr>
 <tr>
  <td colspan="2" align="center"><input type="submit" value="Add Customer"></td>
 </tr>
</table>
</form>

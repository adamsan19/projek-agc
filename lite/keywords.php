<?php
define('IDC', dirname(__FILE__));
$title_panel = 'Keywords';
require 'includes/header.php';
if (isset($_SESSION['login']) && $_SESSION['login'] == $hash) { ?>
<?php if(isset($_GET['suggest'])){ ?>
<div class="container-fluid">
<div class="d-sm-flex align-items-center justify-content-between mb-4">
<h1 class="h3 mb-0 text-gray-800">Google Suggest</h1>
<a href="keywords.php" class="d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-key fa-sm text-white-50"></i> Keywords</a>
</div>
<?php require '../core/functions/keyword.php'; ?>
<div class="row">
<div class="col-md-12">
<div class="card">
<form action="" method="post">
<div class="card-body">
<div class="card bg-primary text-white shadow mb-2">
<div class="card-body" id="numofkeywords" style="text-align:center">
<b>0 : 0</b>
</div>
</div>
<textarea name="injek_kw" id="injek_kw" class="form-control" rows="8" required="required"></textarea>
</div>
<div class="card-body">
<select class="form-control" name="min" id="min" onchange="FilterIfNotWorking()">
<option value="0">Minimum number of words</option>
<option value="1">1</option>
<option value="2">2</option>
<option value="3">3</option>
<option value="4">4</option>
<option value="5">5</option>
<option value="6">6</option>
<option value="7">7</option>
<option value="8">8</option>
<option value="9">9</option>
</select></div>
<div class="row">
<div class="col-md-6">
<div class="card-body">
<textarea id="filter-positive" class="form-control" rows="4" onkeyup="FilterIfNotWorking()" placeholder="Positive Filter"></textarea>
</div></div>
<div class="col-md-6">
<div class="card-body">
<textarea id="filter-negative" class="form-control" rows="4" onkeyup="FilterIfNotWorking()" placeholder="Negative Filter"></textarea>
</div></div>
</div>
<div class="card-footer">
<button class="btn btn-primary" id="startjob" onclick="StartJob();" type="button">Generate</button> 
<input id="submit_injek" type="submit" class="btn btn-success" type="submit" value="Inject">
</div>
</form>
</div></div>
</div>
</div>
<script type="text/javascript">

var keywordsToDisplay = new Array();
var hashMapResults = {};
var numOfInitialKeywords = 0;
var doWork = false;
var keywordsToQuery = new Array();
var keywordsToQueryIndex = 0;
var queryflag = false;

window.setInterval(DoJob, 750);

function StartJob()
{
if(doWork == false)
{
keywordsToDisplay = new Array();
hashMapResults = {};
keywordsToQuery = new Array();
keywordsToQueryIndex = 0;

hashMapResults[""] = 1;
hashMapResults[" "] = 1;
hashMapResults["  "] = 1;

var ks = $('#injek_kw').val().split("\n");
var i = 0;
for(i = 0; i < ks.length; i++)
{
keywordsToQuery[keywordsToQuery.length] = ks[i];
keywordsToDisplay[keywordsToDisplay.length] = ks[i];

var j = 0;
for(j = 0; j < 26; j++)
{
var chr = String.fromCharCode(97 + j);
var currentx = ks[i] + ' ' + chr;
keywordsToQuery[keywordsToQuery.length] = currentx;
hashMapResults[currentx] = 1;
}
}
//document.getElementById("input").value = '';
//document.getElementById("input").value += "\n";
numOfInitialKeywords = keywordsToDisplay.length;
FilterAndDisplay();

doWork = true;
$('#startjob').html('Stop Generator');
}
else
{
doWork = false;
alert("Stopped");
$('#startjob').html('Generate');
}
}

function DoJob()
{
if(doWork == true && queryflag == false)
{
if(keywordsToQueryIndex < keywordsToQuery.length)
{
var currentKw = keywordsToQuery[keywordsToQueryIndex];
QueryKeyword(currentKw);
keywordsToQueryIndex++;
}
else
{
if (numOfInitialKeywords != keywordsToDisplay.length)
{
alert("Done");
doWork = false;
$('#startjob').val('Start Job');
}
else
{
keywordsToQueryIndex = 0;
}
}
}
}

function QueryKeyword(keyword)
{
var querykeyword = keyword;
//var querykeyword = encodeURIComponent(keyword);
var queryresult = '';
queryflag = true;

$.ajax({
url: "https://suggestqueries.google.com/complete/search",
jsonp: "jsonp",
dataType: "jsonp",
data: {
q: querykeyword,
client: "chrome"
},
success: function(res) {
var retList = res[1];

var i = 0;
for(i = 0; i < retList.length; i++)
{
var currents = CleanVal(retList[i]);
if(hashMapResults[currents] != 1)
{
hashMapResults[currents] = 1;
keywordsToDisplay[keywordsToDisplay.length] = CleanVal(retList[i]);

keywordsToQuery[keywordsToQuery.length] = currents;

var j = 0;
for(j = 0; j < 26; j++)
{
var chr = String.fromCharCode(97 + j);
var currentx = currents + ' ' + chr;
keywordsToQuery[keywordsToQuery.length] = currentx;
hashMapResults[currentx] = 1;
}
}
}
FilterAndDisplay();
var textarea = document.getElementById("injek_kw");
textarea.scrollTop = textarea.scrollHeight;
queryflag = false;
}
});
}

function CleanVal(injek_kw)
{
var val = injek_kw;
val = val.replace("\\u003cb\\u003e", "");
val = val.replace("\\u003c\\/b\\u003e", "");
val = val.replace("\\u003c\\/b\\u003e", "");
val = val.replace("\\u003cb\\u003e", "");
val = val.replace("\\u003c\\/b\\u003e", "");
val = val.replace("\\u003cb\\u003e", "");
val = val.replace("\\u003cb\\u003e", "");
val = val.replace("\\u003c\\/b\\u003e", "");
val = val.replace("\\u0026amp;", "&");
val = val.replace("\\u003cb\\u003e", "");
val = val.replace("\\u0026", "");
val = val.replace("\\u0026#39;", "'");
val = val.replace("#39;", "'");
val = val.replace("\\u003c\\/b\\u003e", "");
val = val.replace("\\u2013", "2013");
if (val.length > 4 && val.substring(0, 4) == "http") val = "";
return val;
}

function Filter(listToFilter)
{
var retList = listToFilter;

if (document.getElementById("filter-positive").value.length > 0)
{
var filteredList = new Array();
var filterContains = document.getElementById("filter-positive").value.split("\n");
var i = 0;
for (i = 0; i < retList.length; i++)
{
var currentKeyword = retList[i];
var boolContainsKeyword = false;
var j = 0;
for (j = 0; j < filterContains.length; j++)
{
if (filterContains[j].length > 0)
{
if (currentKeyword.indexOf(filterContains[j]) != -1)
{
boolContainsKeyword = true;
break;
}
}
}

if (boolContainsKeyword)
{
filteredList[filteredList.length] = currentKeyword;
}
}

retList = filteredList;
}

if (document.getElementById("filter-negative").value.length > 0)
{
var filteredList = new Array();
var filterContains = document.getElementById("filter-negative").value.split("\n");
var i = 0;
for (i = 0; i < retList.length; i++)
{
var currentKeyword = retList[i];
var boolCleanKeyword = true;
var j = 0;
for (j = 0; j < filterContains.length; j++)
{
if (filterContains[j].length > 0)
{
if (currentKeyword.indexOf(filterContains[j]) >= 0)
{
boolCleanKeyword = false;
break;
}
}
}

if (boolCleanKeyword)
{
filteredList[filteredList.length] = currentKeyword;
}
}

retList = filteredList;
}

var e = document.getElementById("min");
var min = e.options[e.selectedIndex].value;

if(min > 0){
var filteredList = new Array();
var regex = /\s+/gi;

for (i = 0; i < retList.length; i++)
{
var currentKeyword = retList[i];
var boolExceedMinimum = false;

var wordCount = 0;

if(currentKeyword != null){
wordCount = currentKeyword.trim().replace(regex, ' ').split(' ').length;
}

if (wordCount >= min)
{
filteredList.push(currentKeyword);
}
}

retList = filteredList;

}

return retList;
}

function FilterAndDisplay()
{
var i = 0;
var sb = '';
var outputKeywords = Filter(keywordsToDisplay);
for (i = 0; i < outputKeywords.length; i++)
{
sb += outputKeywords[i];
sb += '\n';
}
document.getElementById("injek_kw").value = sb;
document.getElementById("numofkeywords").innerHTML = '' + outputKeywords.length + ' : ' + keywordsToDisplay.length;
}

function FilterIfNotWorking()
{
if (doWork == false)
{
FilterAndDisplay();
}
}

</script>
<?php }else{ ?>
<div class="container-fluid">
<div class="d-sm-flex align-items-center justify-content-between mb-4">
<h1 class="h3 mb-0 text-gray-800">Keywords</h1>
<a href="?suggest" class="d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-search fa-sm text-white-50"></i> Google Suggest</a>
</div>
<?php require '../core/functions/keyword.php'; ?>
<div class="row">
<div class="col-md-12">
<div class="card">
<form action="" method="post">
<div class="card-body">
<div class="card bg-primary text-white shadow mb-2">
<div class="card-body">
Separate by new line
</div>
</div>
<textarea class="form-control" rows="15" name="injek_kw" id="injek_kw" required></textarea>
</div><div class="card-footer">
<input id="submit_injek" class="btn btn-primary" value="Submit" name="submit" type="submit" />
<a class="btn btn-danger" href="index.php">Back</a>
</div>
</form>
</div></div></div>
</div>
<?php } ?>
<?php
require 'includes/footer.php';
}else{
header('Location: ' . lite_url('login.php'));
exit;
}?>
<?php /* ***** Orca Search - Search Engine XHTML Output *************


*********************************************************************
******** Introduction ***********************************************
The body series of files are designed to take output generated by the
"head.php" file and translate it into a format understandable by a
client, like a web browser or feed reader.  In plain english,
"head.php" performs all the searching and relevancy operations, and
the body files show the resulting output.

This file will generate XHTML output of search results.  You can set
a couple options to your liking which are briefly explained below:

The $resultTemplate is the HTML template used for each search result.
This template uses codes which are replaced by search engine output
for each search result displayed.  The available codes and their
meanings are:

{R_NUMBER}      - Number of the result in the result list
{R_RELEVANCE}   - Relevance score to one decimal place
{R_URI}         - Result URI, suitable for linking
{R_MATCHURI}*   - Result URI with highlighted matching terms
{R_FILETYPE}    - Result filetype
{R_CATEGORY}    - Result Category
{R_TITLE}       - Result Title
{R_DESCRIPTION} - Result Description
                  - If there is no page description, the first 200
                    characters of body text will be used instead
{R_MATCH}       - A selection of body text with highlighted matches
                  - If there is no match-text, the contents of the
                    description will be used instead

* - Even though the JWriter uses a similar system of codes, these
codes are only available for online output.

$resultsPerPage lets you define how many search results to display
per page before extending results to multiple pages (pagination).

To modify this file to deliver HTML, just do a global replace of
" />" with ">" (without the quotes, include the space!)


*********************************************************************
******** Import Language File ************************************ */
if ($langfile = @fopen("{$_SERVER['DOCUMENT_ROOT']}/{$_SDATA['directory']}/body.xhtml.lang.txt", "r") or
    $langfile = @fopen("./{$_SDATA['directory']}/body.xhtml.lang.txt", "r") or
    $langfile = @fopen("os2/body.xhtml.lang.txt", "r") or
    $langfile = @fopen("body.xhtml.lang.txt", "r")) {
  while (!feof($langfile)) {
    $line = fgets($langfile);
    if (strpos($line, "=") && $line{0} != "#") {
      $line = explode("=", $line, 2);
      if (trim($line[1]) == "{") {
        $_OLANG[$line[0]] = "";
        while (trim($multiline = fgets($langfile)) != "}" && !feof($langfile))
          $_OLANG[$line[0]] .= trim($multiline)."\n";
        $_OLANG[$line[0]] = trim($_OLANG[$line[0]]);
      } else $_OLANG[$line[0]] = rtrim($line[1]);
    }
  }
  fclose($langfile);
} else $_DDATA['online'] = false;


/* ******************************************************************
******** User Setup ********************************************** */
$resultsPerPage = 10;
$resultTemplate = <<<ORCA
<h3>
  <span class="filetype">{R_FILETYPE}</span>
  <a href="{R_URI}" title="{R_DESCRIPTION}">{R_TITLE}</a>
  <small title="{$_OLANG['00g']}">{R_CATEGORY}</small>
</h3>
<blockquote>
  <p>
    {R_MATCH}<br />
    <cite>{R_URIMATCH}</cite> <small>({R_RELEVANCE})</small>
  </p>
</blockquote>
ORCA;


/* ******************************************************************
******** Script Setup ******************************************** */
$_SDATA['root'] = "{$_SDATA['protocol']}://{$_SERVER['HTTP_HOST']}".preg_replace("/\/[^\/]*$/", "/", $_SERVER['PHP_SELF']);
$_SDATA['find'] = array(
  "{R_NUMBER}",
  "{R_RELEVANCE}",
  "{R_URI}",
  "{R_URIMATCH}",
  "{R_FILETYPE}",
  "{R_CATEGORY}",
  "{R_TITLE}",
  "{R_DESCRIPTION}",
  "{R_MATCH}"
);

if (isset($_QUERY)) {
  $ignoredTerms = array_filter(array_diff($_QUERY['allterms'], $_QUERY['terms']));
  $ignoredTerms = ($ignoredTermsCount = count($ignoredTerms)) ? implode(" ", $ignoredTerms) : "";
}
if (!isset($_QUERY['category'])) $_QUERY['category'] = (isset($_GET['c'])) ? $_GET['c'] : "";
if (!isset($_QUERY['original'])) $_QUERY['original'] = "";


/* ******************************************************************
******** Output ************************************************** */
?><div id="os_main">

  <!-- Orca Search v<?php echo $_SDATA['version']; ?> -->

  <style type="text/css">@import url(<?php echo $_SDATA['directory']; ?>/body.xhtml.css);</style>

  <p id="os_resultbar">
    <?php if ($_DDATA['online'] && count($_RESULTS)) {
      $_GET['start'] = (!isset($_GET['start'])) ? 1 : ((count($_RESULTS) <= $resultsPerPage) ? 1 : (int)$_GET['start']);
      $_GET['end'] = min($_GET['start'] + $resultsPerPage - 1, count($_RESULTS));
      printf($_OLANG['000'], $_GET['start'], $_GET['end'], count($_RESULTS), trim(htmlspecialchars($_QUERY['original'])), sprintf("%01.2f", array_sum(explode(" ", microtime())) - $_SDATA['now']));
    } else echo "&nbsp;"; ?> 
  </p>


  <?php /* ***** No language file ******************************** */
  if (!isset($_OLANG)) { ?> 
    <p class="os_msg">
      Unable to load language file!
    </p>

  <?php /* ***** No DB Connection ******************************** */
  } else if (!$_DDATA['online']) { ?> 
    <p class="os_msg">
      <?php echo $_OLANG['001']; ?><br />
      <em><?php echo $_DDATA['errno'], ": ", $_DDATA['error']; ?></em>
    </p>

  <?php /* ***** DB Locked *************************************** */
  } else if ($_RESULTS === NULL) { ?> 
    <p class="os_msg">
      <?php echo $_OLANG['002']; ?> 
    </p>

  <?php /* ***** List Results ************************************ */
  } else {
    if ($_QUERY['category']) { ?> 
      <p class="os_msg">
        <?php printf($_OLANG['00h'], $_QUERY['category']); ?> 
      </p><?php
    }

    /* ***** Query Success *************************************** */
    if (count($_RESULTS)) {
      if ($ignoredTermsCount) { ?> 
        <p class="os_msg">
          <?php printf($_OLANG['003'], htmlspecialchars($ignoredTerms)); ?> 
        </p><?php
      } ?> 

      <ol id="os_results" start="<?php echo $_GET['start']; ?>">
        <?php for ($x = $_GET['start'] - 1; $x < $_GET['end']; $x++) {
          $_RESULTS[$x]['filetype'] = (!in_array($_RESULTS[$x]['filetype'], array("html", "txt"))) ? "[{$_RESULTS[$x]['filetype']}]" : "";
          if (!$_RESULTS[$x]['title']) {
            $puri = @parse_url($_RESULTS[$x]['uri']);
            if (isset($puri['path'])) $_RESULTS[$x]['title'] = basename($puri['path']);
            if (strlen($_RESULTS[$x]['title']) <= 3)
              $_RESULTS[$x]['title'] = htmlspecialchars(str_replace(array($_SDATA['root'], "{$_SDATA['protocol']}://"), array("/", ""), $_RESULTS[$x]['uri']));
          }
          $_RESULTS[$x]['uri'] = str_replace(array($_SDATA['root'], "{$_SDATA['protocol']}://{$_SERVER['HTTP_HOST']}/"), array("", "/"), $_RESULTS[$x]['uri']);
          $_RESULTS[$x]['matchURI'] = str_replace(array($_SDATA['root'], "{$_SDATA['protocol']}://{$_SERVER['HTTP_HOST']}/", "https://"), array("/", "/", ""), $_RESULTS[$x]['matchURI']);
          $_SDATA['repl'] = array(
            $x + 1,
            sprintf("%01.1f", $_RESULTS[$x]['relevance']),
            str_replace(array("{", "}"), array("&#123;", "&#125;"), $_RESULTS[$x]['uri']),
            str_replace(array("{", "}"), array("&#123;", "&#125;"), $_RESULTS[$x]['matchURI']),
            $_RESULTS[$x]['filetype'],
            (count($_SDATA['categories']) > 1)
              ? "- ".str_replace(array("{", "}"), array("&#123;", "&#125;"), htmlspecialchars($_RESULTS[$x]['category']))
              : "",
            str_replace(array("{", "}"), array("&#123;", "&#125;"), $_RESULTS[$x]['title']),
            str_replace(array("{", "}"), array("&#123;", "&#125;"), $_RESULTS[$x]['description']),
            str_replace(array("{", "}"), array("&#123;", "&#125;"), $_RESULTS[$x]['matchText'])
          ); ?> 
          <li>
            <?php echo str_replace(array("&#123;", "&#125;"), array("{", "}"), str_replace($_SDATA['find'], $_SDATA['repl'], $resultTemplate)); ?> 
          </li>
        <?php } ?> 
      </ol>

      <?php /* ***** Show Pagination Links *********************** */
      if (count($_RESULTS) > $resultsPerPage) {
        $qstr = htmlspecialchars(preg_replace("/&start=\d+/i", "", $_SERVER['QUERY_STRING'])); ?> 
        <div id="os_pagination">
          <div id="os_pagin1">
            <?php if ($_GET['start'] > 1) {
              $prev = max(1, ($_GET['start'] - $resultsPerPage)); ?> 
              <a href="<?php echo $_SERVER['PHP_SELF'], "?{$qstr}&amp;start=$prev"; ?>" title="<?php echo $_OLANG['004']; ?>">&lt;&lt; <?php echo $_OLANG['004']; ?></a>
            <?php } else echo "&nbsp;"; ?> 
          </div>
          <div id="os_pagin3">
            <?php if ($_GET['end'] < count($_RESULTS)) {
              $next = $_GET['end'] + 1; ?> 
              <a href="<?php echo $_SERVER['PHP_SELF'], "?{$qstr}&amp;start=$next"; ?>" title="<?php echo $_OLANG['005']; ?>"><?php echo $_OLANG['005']; ?> &gt;&gt;</a>
            <?php } else echo "&nbsp;"; ?> 
          </div>
          <div id="os_pagin2">
            <?php $pagemax = ceil(count($_RESULTS) / $resultsPerPage);
            for ($x = 1; $x <= $pagemax; $x++) {
              $list = ($x - 1) * $resultsPerPage + 1;
              if ($list == $_GET['start']) { ?> 
                <strong><?php echo $x; ?></strong>
              <?php } else {
                $title = $list." - ".min($list + $resultsPerPage - 1, count($_RESULTS)); ?> 
                <a href="<?php echo $_SERVER['PHP_SELF'], "?{$qstr}&amp;start=$list"; ?>" title="<?php echo $title; ?>"><?php echo $x; ?></a>
              <?php }
            } ?> 
          </div>
        </div>
      <?php }

    /* ***** No Query or No Results ****************************** */
    } else { ?> 
      <p class="os_msg">
        <?php if ($_QUERY['original']) {
          printf($_OLANG['006'], trim(htmlspecialchars($_QUERY['original'])));
          if ($ignoredTermsCount) echo "\n<br />", sprintf($_OLANG['003'], htmlspecialchars($ignoredTerms));
          if ($_QUERY['category'] != "") echo "\n<br /><br />", sprintf($_OLANG['007'], $_SERVER['REQUEST_URI'], htmlspecialchars($_QUERY['original']));
        } else echo $_OLANG['008']; ?> 
      </p>
      <ul>
        <li><?php printf($_OLANG['009'], $_VDATA['s.termlength']); ?></li>
        <li><?php echo $_OLANG['00a']; ?></li>
        <li><?php echo $_OLANG['00b']; ?></li>
        <li><?php echo $_OLANG['00c']; ?></li>
      </ul>
    <?php }
  } ?> 

  <?php /* ***** Search Form ********************************** */ ?> 
  <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" id="os_search">
    <div>
      <?php if (isset($_OLANG)) { ?> 
        <input type="text" name="q" value="<?php echo htmlspecialchars($_QUERY['original']); ?>" /><?php
        if (isset($_SDATA['categories']) && count($_SDATA['categories']) > 1) { ?> 
          <label>
            &nbsp; <?php echo $_OLANG['00d']; ?> 
            <select name="c" size="1">
              <option value=""<?php if ($_QUERY['category'] == "") echo " selected=\"selected\""; ?>><?php echo $_OLANG['00e']; ?></option><?php
              foreach ($_SDATA['categories'] as $category) { ?> 
                <option value="<?php echo $category; ?>"<?php
                  if ($_QUERY['category'] == $category) echo " selected=\"selected\"";
                  ?>><?php echo $category; ?></option><?php
              } ?> 
            </select>
          </label><?php
        } ?> 
        <input type="submit" value="<?php echo $_OLANG['00f']; ?>" />
      <?php } else echo "&nbsp;"; ?> 
    </div>
  </form>

  <div style="text-align:center;font:italic 80% Arial,sans-serif;">
    <hr style="width:60%;margin:10px auto 2px auto;" />
    An <a href="http://www.greywyvern.com/" title="GreyWyvern.com">Orca</a> Script
  </div>
</div>
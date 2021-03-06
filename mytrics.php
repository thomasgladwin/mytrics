<?php require($_SERVER['DOCUMENT_ROOT'].'/header.inc'); ?>
<?php require($_SERVER['DOCUMENT_ROOT'].'/menu.inc'); ?>

<div class="content">
<h1>Mytrics</h1>
<?php
	# https://www.tegladwin.com/Misc/2021_01_27_Scopus/mytrics.php?auid=12763755600
	
	$apikey = "xxx";
	$orcid = ""; // "0000-0001-9538-6425"
	$auid = "";  // "12763755600"
?>
<body>
<p>Credit to the <a href="https://dev.elsevier.com/">Scopus API</a>.</p>
<p>Usage: Enter a Scopus author ID or an ORCID in the corresponding box and click the button to retrieve metrics. <a href="https://libguides.lb.polyu.edu.hk/research_visibility/scopusid#s-lib-ctab-21088753-1">How to find your Scopus author ID</a> (make sure you're signed in with sufficient authorization to see it, and not just the default ORCID). 
<p>Note: For citations-per-year descriptives I only included papers published at least a year ago.</p>
<p>The output includes the h-factor and a variant I called the H5, which is the h-factor for papers published within the last 5 years. The H5 seems potentially useful as a window on more recent work, as opposed to the standard h-factor that can only increase over time. This might allow more relevant comparisons / better incentives in certain contexts, although all use of such metrics requires careful thought and caution to make sure they're used for good (but of course - so does not using metrics, and metrics do seem to me to inherently let themselves be relatively transparently analyzed and criticized). Generally, I'd say that it's clear that h-factors and similar metrics can be gamed or otherwise influenced in ways that make comparisons and interpretation complex, so in the first instance I'd say they're mainly an upper bound of influence. Even with that in mind, it's often difficult to attribute metrics to an individual's personal contributions in the context of co-authorship cultures. And beyond that, there's the fundamental question of which qualities or features are likely to be related to citation metrics - are those necessarily all the good ones? Nevertheless, when used honestly and fairly I think metrics can be helpful, e.g., in identifying where work may not be being picked up, at the level of particular groups or of particular types of paper.</p>

<form id = "IntroForm" action = "mytrics.php" method = "get">
	<p><textarea rows="2" name="auid"></textarea>
	<input type="submit" value="Use Scopus ID">
</form>

<form id = "IntroForm" action = "mytrics.php" method = "get">
	<p><textarea rows="2" name="orcid"></textarea>
	<input type="submit" value="Use ORCID">
</form>

<?php
	if (isset($_GET["auid"])) {
		$auid = $_GET["auid"];
	} elseif (isset($_GET["orcid"])) {
		$orcid = $_GET["orcid"];
		$orcid = "".str_replace("-", "", $orcid);
	}

	$GLOBALS["memory0"]["citedby-count"] = array();	
	$GLOBALS["memory0"]["prism:coverDate"] = array();	
	$GLOBALS["memory0"]["title"] = array();	

	function datawalkshow($item, $key) {
		// echo $key."=".$item."<br>";
		if ($key == "citedby-count") {
			//echo $item."<br>";
			array_push($GLOBALS["memory0"]["citedby-count"], $item);
			$GLOBALS["memory0"]["NThisStep"] = $GLOBALS["memory0"]["NThisStep"] + 1;
		}
		if ($key == "prism:coverDate") {
			//echo $item."<br>";
			array_push($GLOBALS["memory0"]["prism:coverDate"], $item);
		}
		if ($key == "dc:title") {
			// echo $key." = ".$item."<br>";
			array_push($GLOBALS["memory0"]["title"], $item);
		}
	}
	
	//echo '<object height="50" data="https://api.elsevier.com/content/abstract/citation-count?doi=10.1016/S0014-5793(01)03313-0&httpAccept=text/html&apiKey='.$apikey.'"></object>';

	$GLOBALS["memory0"]["NThisStep"] = 1;
	$step0 = 0;
	$safetyCount = 20;
	while ($GLOBALS["memory0"]["NThisStep"] > 0 && $step0 < $safetyCount) {
		
		$start0 = 25 * $step0;
		$step0 = $step0 + 1;
		$GLOBALS["memory0"]["NThisStep"] = 0;
		
		//echo "<p>Step ".$step0.", start = ".$start0.", safetyCount = ".$safetyCount."</p>";
		
		if (!empty($auid)) {
			$url = "https://api.elsevier.com/content/search/scopus?query=AU-ID(".$auid.")&start=".$start0."&field=citedby-count,prism:coverDate,dc:title&apikey=4ea474635332968b121ff20370242883";
		} elseif (!empty($orcid)) {
			$url = "https://api.elsevier.com/content/search/scopus?query=ORCID(".$orcid.")&start=".$start0."&field=citedby-count,prism:coverDate,dc:title&apikey=4ea474635332968b121ff20370242883";
		} else {
			echo '<p>Provide your ID to get results.</p>';
			die();
		}

		try {
			$response_xml_data = file_get_contents($url);
		} catch (Throwable $t) {
			echo '<p>Throw: '.$t."</p>";
		} catch (Exception $e) {
			echo '<p>Exception: '.$e."</p>";
		}
		
		$data = json_decode($response_xml_data, true);
		
		array_walk_recursive($data, 'datawalkshow');
		
		// print_r($data);
		// print_r($GLOBALS["memory0"]["citedby-count"]);
		//echo '<p>N new found: '.$GLOBALS["memory0"]["NThisStep"].'</p>';
	}
	
	// Metrics
	$citeHist = array();
	for ($n = 0; $n < $max_cc; $n++) {
		$citeHist[$n] = 0;
	}
	$ccPerYear = array();
	$ccPerYear1 = array();
	$pubAgeArray = array();
	$max_cc = 0;
	$N_Papers_5 = 0;
	$total0 = 0;
	$total5 = 0;
	for ($n = 0; $n < count($GLOBALS["memory0"]["citedby-count"]); $n++) {
		$cc = $GLOBALS["memory0"]["citedby-count"][$n];
		$pd = $GLOBALS["memory0"]["prism:coverDate"][$n];
		$pub_age = date_create("now")->getTimestamp() - date_create($GLOBALS["memory0"]["prism:coverDate"][$n])->getTimestamp();
		$pub_age = $pub_age/(365*24*60*60);
		$pub_age = round($pub_age, 2);
		if ($pub_age < 1/12) {
			$pub_age = 1/12;
		}
		$citeHist[$cc] = $citeHist[$cc] + 1;
		array_push($ccPerYear, $cc/$pub_age);
		if ($pub_age > 1) {
			array_push($ccPerYear1, $cc/$pub_age);
		}
		array_push($pubAgeArray, $pub_age);
		if ($pub_age < 5) {
			$N_Papers_5++;
		}
		if ($cc > $max_cc) {
			$max_cc = $cc;
		}
		$total0 += $cc;
		if ($pub_age < 5) {
			$total5 += $cc;
		}
	}
	
	// H-factor
	$h = 0;
	$h5 = 0;
	for ($cc4h = 0; $cc4h < $max_cc; $cc4h++) {
		$n_papers_over_cc = 0;
		$n_papers_over_cc5 = 0;
		for ($n = 0; $n < count($GLOBALS["memory0"]["citedby-count"]); $n++) {
			if ($GLOBALS["memory0"]["citedby-count"][$n] >= $cc4h) {
				$n_papers_over_cc = $n_papers_over_cc + 1;
				if ($pubAgeArray[$n] < 5) {
					$n_papers_over_cc5++;
				}
			}
		}
		if ($n_papers_over_cc >= $cc4h) {
			$h = $cc4h;
		}
		if ($n_papers_over_cc5 >= $cc4h) {
			$h5 = $cc4h;
		}
	}
	echo "<p>H-factor = ".$h."</p>";
	echo "<p>H5-factor = ".$h5." (h-factor for papers less than 5 years old)</p>";
	
	// Total
	
	echo '<p>Total citations = '.$total0."; citations of papers published in the last 5 years = ".$total5."</p>";
	
	// Median cites per year
	sort($ccPerYear1);
	$medianCitePerYear = round($ccPerYear1[round(count($ccPerYear1)/2)], 2);
	echo "<p>Median citations per year per paper = ".$medianCitePerYear."</p>";
	
	$lowQ = round($ccPerYear1[round(1*count($ccPerYear1)/4)], 2);
	$topQ = round($ccPerYear1[round(3*count($ccPerYear1)/4)], 2);
	echo "<p>3rd quartile citations per year per paper = ".$topQ."</p>";
	
	// Mean cites per year
	$s = 0;
	$n = 0;
	for ($ii = 0; $ii < count($ccPerYear1); $ii++) {
		$s += $ccPerYear1[$ii];
		$n++;
	}
	$meanCitePerYear = round($s/$n, 2);
	echo "<p>Mean citations per year per paper = ".$meanCitePerYear."</p>";
	
	// Median papers per year
	$NPPY = $N_Papers_5/5;
	echo "<p>Median published papers per year (over the last five years) = ".$NPPY."</p>";

	// Prognosis
	$yearsAhead = 10;
	$max_cc_global = $max_cc + $yearsAhead*$topQ*$NPPY + 100;
	echo "<p>Predicted h-factor (with simulated new pubs) in:</p>";
	for ($yr = 1; $yr < $yearsAhead; $yr++) {
		$h = 0;
		for ($cc4h = 0; $cc4h < $max_cc_global; $cc4h++) {
			$n_papers_over_cc = 0;
			for ($n = 0; $n < count($GLOBALS["memory0"]["citedby-count"]); $n++) {
				$thisCitePerYear = $ccPerYear[$n];
				if ($thisCitePerYear == 0 && $pubAgeArray[$n] < 1) {
					$thisCitePerYear = $medianCitePerYear;
				}
				if ($GLOBALS["memory0"]["citedby-count"][$n] + $yr*$thisCitePerYear >= $cc4h) {
					$n_papers_over_cc = $n_papers_over_cc + 1;
				}
			}
			if ($n_papers_over_cc >= $cc4h) {
				$h = $cc4h;
			}
		}
		echo "<p>".$yr." years = ".$h." ";
		$h = 0;
		for ($cc4h = 0; $cc4h < $max_cc_global; $cc4h++) {
			$n_papers_over_cc = 0;
			for ($n = 0; $n < count($GLOBALS["memory0"]["citedby-count"]); $n++) {
				$thisCitePerYear = $ccPerYear[$n];
				if ($thisCitePerYear == 0 && $pubAgeArray[$n] < 1) {
					$thisCitePerYear = $medianCitePerYear;
				}
				if ($GLOBALS["memory0"]["citedby-count"][$n] + $yr*$thisCitePerYear >= $cc4h) {
					$n_papers_over_cc = $n_papers_over_cc + 1;
				}
			}
			//echo "<<".($n_papers_over_cc)."-";
			for ($sy = 0; $sy < $yr; $sy++) {
				if (($yr-$sy)*$lowQ >= $cc4h) {
					$n_papers_over_cc = $n_papers_over_cc + $NPPY*0.25;
				}
				if (($yr-$sy)*$medianCitePerYear >= $cc4h) {
					$n_papers_over_cc = $n_papers_over_cc + $NPPY*0.5;
				}
				if (($yr-$sy)*$topQ >= $cc4h) {
					//echo "<p>".($n_papers_over_cc)."</p>";
					$n_papers_over_cc = $n_papers_over_cc + $NPPY*0.25;
					//echo "<p>".($NPPY*0.25)."</p>";
					//echo "<p>".($n_papers_over_cc)."</p>";
				}
			}
			//echo ($n_papers_over_cc).">>";
			if ($n_papers_over_cc >= $cc4h) {
				$h = $cc4h;
			}
		}
		echo " / ".$h."</p>";
	}
	
	// Overview by cite counts
	echo '<hr><p>List by citation count</p>';
	$i0 = 1;
	for ($cc0 = 0; $cc0 <= $max_cc; $cc0++) {
		$cc = $max_cc - $cc0;
		for ($n = 0; $n < count($GLOBALS["memory0"]["citedby-count"]); $n++) {
			if ($GLOBALS["memory0"]["citedby-count"][$n] == $cc) {
				echo "<p>#".$i0.": ".$cc." cites since ".$GLOBALS["memory0"]["prism:coverDate"][$n].": ".$GLOBALS["memory0"]["title"][$n]."</p>";
				$i0++;
			}
		}
	}
	
	// Overview by date (as returned by Scopus)
	echo '<hr><p>List by date</p>';
	$max_cc = 0;
	for ($n = 0; $n < count($GLOBALS["memory0"]["citedby-count"]); $n++) {
		$ti = $GLOBALS["memory0"]["title"][$n];
		$cc = $GLOBALS["memory0"]["citedby-count"][$n];
		$pd = $GLOBALS["memory0"]["prism:coverDate"][$n];
		$pub_age = date_create("now")->getTimestamp() - date_create($GLOBALS["memory0"]["prism:coverDate"][$n])->getTimestamp();
		$pub_age = $pub_age/(365*24*60*60);
		$pub_age = round($pub_age, 2);
		echo "<p>".$cc." citations in ".$pub_age." years. ".$ti." (".$pd.")</p>";
		if ($cc > $max_cc) {
			$max_cc = $cc;
		}
	}	
?>
</div>
</body>
</html>

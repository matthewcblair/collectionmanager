<?php

ini_set('display_startup_errors',1);
ini_set('display_errors',1);
error_reporting(-1);

session_start();

$consumer_key = "***REDACTED***";
$consumer_secret = "***REDACTED***";
$request_token_path = "https://api.discogs.com/oauth/request_token";
$scope = 'http://api.discogs.com';

require 'oauth.php';

$oauthObject = new OAuthSimple();
$scope = 'http://api.discogs.com';

if(isset($_GET['clean'] ) ) $_SESSION['state'] = 0;

$signatures = array( 'consumer_key' => $consumer_key,
'shared_secret' => $consumer_secret);

if (!isset($_SESSION['state']))
    $_SESSION['state'] = 1;

// Collect the user's discogs credentials via oauth authentication
if(!isset($_GET['oauth_token']) && isset($_SESSION['state']) && $_SESSION['state']==1) $_SESSION['state'] = 0;
try {

    if(!isset($_GET['oauth_token']) && !$_SESSION['state']) {

        $result = $oauthObject->sign(array(
        'path' =>'http://api.discogs.com/oauth/request_token',
        'parameters'=> array(
        'scope' => $scope,
        'oauth_callback'=> 'http://lollookup.com'),
        'signatures'=> $signatures));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, 'discogs_api +http://lollookup.com');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $result['signed_url']);

        $r = curl_exec($ch);

        curl_close($ch);

        parse_str($r, $returned_items);
        $request_token = $returned_items['oauth_token'];
        $_SESSION['secret'] = $request_token_secret = $returned_items['oauth_token_secret'];
        $_SESSION['state'] = 1;

        $result = $oauthObject->sign(array(
        'path' =>'http://www.discogs.com/oauth/authorize',
        'parameters'=> array(
        'oauth_token' => $request_token),
        'signatures'=> $signatures));

        header("Location:$result[signed_url]");
        exit;
        } else if($_SESSION['state']==1) {

        $signatures['oauth_secret'] = $_SESSION['secret'];
        $signatures['oauth_token'] = $_GET['oauth_token'];

        $result = $oauthObject->sign(array(
        'path' => 'http://api.discogs.com/oauth/access_token',
        'parameters'=> array(
        'oauth_verifier' => $_GET['oauth_verifier'],
        'oauth_token' => $_GET['oauth_token']),
        'signatures'=> $signatures));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, 'discogs_api +http://lollookup.com');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
        $r = curl_exec($ch);

        parse_str($r, $returned_items);
        $_SESSION['state'] = 2;


        $_SESSION['token'] = $access_token = $returned_items['oauth_token'];
        $_SESSION['secret'] = $access_token_secret = $returned_items['oauth_token_secret'];
        
    }

    $oauth_props = array( 'oauth_token' => $_SESSION['token'],
    'oauth_secret' => $_SESSION['secret'],
    'consumer_key' => $consumer_key,
    'shared_secret' => $consumer_secret);
    
    
    
    
    $oauthObject->reset();
    $params['path'] = "$scope/oauth/identity";
    $params['signatures'] = $oauth_props;
    
    if( isset($_GET['q']) ) $params['parameters'] = '';
    else $params['parameters'] = '';


    $result = $oauthObject->sign($params);
    
    $url = $result['signed_url'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, 'discogs_api +http://lollookup.com');
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
    $json_user_info = curl_exec($ch);
    curl_close($ch);
    $user_info = json_decode($json_user_info, true);
    //$username = '';

?>
    
<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
  <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
</head>
<style>

#moveright {
    position:relative;
    right: -15px;
}
</style>
<?php
echo '<body>';
$servername = "localhost";
$username = "";
$password = "";
$dbname = "";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    echo "<br><br>died a cruel death<br><br>";
    die("Connection failed: " . $conn->connect_error);
}


echo '<br><br><br>';



if(  !isset($_GET["sort"]) || ( isset($_GET["sort"]) && $_GET["sort"] != "datesort")  )
    $sql = "SELECT id, artist, title_label, format, year, rating, media_cond, sleeve_cond, notes, value, instance_id, vinyl_id FROM records ORDER BY value DESC";
else 
    $sql = "SELECT id, artist, title_label, format, year, rating, media_cond, sleeve_cond, notes, value, instance_id, vinyl_id FROM records ORDER BY instance_id DESC";
    

$result = mysqli_query($conn, $sql);
$db_records = array();

if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $db_records[] = $row;
    }
} else {
    echo "0 results";
}


$total_collection_value = 0;
for($i = 0; $i < count($db_records); $i++) {
    $total_collection_value += $db_records[$i]['value'];
}
 
// Update the collection if the user has chosen to do so
// (pull the latest 100 records from discogs collection, check which are new)

if(isset($_GET["update"])){ 
    
    $oauthObject->reset();
    $params['path'] = "$scope/users/username/collection/folders/0/releases";
    $params['signatures'] = $oauth_props;
    
    if( isset($_GET['q']) ) $params['parameters'] = 'page=1&per_page=100&sort=added&sort_order=desc';
    else $params['parameters'] =                    'page=1&per_page=100&sort=added&sort_order=desc';

    $result = $oauthObject->sign($params);

    $url = $result['signed_url'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, 'discogs_api +lollookup.com');
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    //Execute the curl session
    $output_updated = curl_exec($ch);



    curl_close($ch);
    
    $parsed_json_updated = json_decode($output_updated, true);
    $found_in_db = false;
    $new_records = array();
    for($i = 0; $i < 100; $i++) {
        for($j = 0; $j < count($db_records); $j++){
            if($parsed_json_updated['releases'][$i]['id'] == $db_records[$j]['vinyl_id']) {
                $found_in_db = true;
                break;
            }
        }
        if( !$found_in_db ) { 
            $new_records[] = $parsed_json_updated['releases'][$i];
        }
        
        $found_in_db = false;
    }
    
    // If new records were found in the discogs collection and need to be added to the database ...
    if(count($new_records) != 0) {
        
        for($i = 0; $i < count($new_records); $i++) {
        
            // Formatting the Artist Name
            $extra_text1 = '';
            $extra_text2 = '';
            $artist1_name = str_replace('(2)', '', $new_records[$i]['basic_information']['artists'][0]['name']);
            if($artist1_name == 'Kiss') $extra_text1 = '_(band)';
            if(substr($artist1_name, -5) == ', The') $artist1_name = 'The ' . substr($artist1_name, 0, (strlen($artist1_name) - 5));
            if ( $new_records[$i]['basic_information']['artists'][0]['join'] == "With") {
                $artist2_name = str_replace('(2)', '', $new_records[$i]['basic_information']['artists'][1]['name']);
                if($artist2_name == 'Kiss') $extra_text2 = '_(band)';
                $artist_text = $artist1_name . " With " . $artist2_name;
            }
            
            else {
                $artist_text = $artist1_name;
            }
            
            // Get the title of the record
            $title_text = " - " . $new_records[$i]['basic_information']['title'];
    
            // Label(s) and Catalog Number(s) of the record
            $labels = array();
            $labels = $new_records[$i]['basic_information']['labels'];
            $catnos = array();
            $label_text = ' (';
            if( count($labels) > 1) {
                for($k = 0; $k < (count($labels) - 1); $k++) {
                    $label_text = $label_text . $labels[$k]['name'] . ', ';
                    
                    $catnos[] = $labels[$k]['catno'];
                }
    
                $label_text = $label_text . $labels[count($labels) - 1]['name'] . ' - ';
                for($c = 0; $c < count($catnos); $c++) {
                    $label_text = $label_text . $catnos[$c] . ', ';
                }
                $label_text = $label_text . $labels[count($labels) - 1]['catno'];
            }
            else {
                $label_text = $label_text . $labels[0]['name'] . ' - ' . $labels[0]['catno'] . ')';
            }
            
            unset($catnos);  
                  
            // Format of the record
            $formats = $new_records[$i]['basic_information']['formats'][0]['descriptions'];
            $formats_text = '';
            for($j = 0; $j < (count($formats) - 1); $j++) {
                $short_format = str_replace('ilation', '', $formats[$j]);
                $formats_text = $formats_text . $short_format . ', ';
            }
            $short_format = str_replace('ilation', '', $formats[count($formats) - 1]);
            $formats_text = $formats_text . $short_format;
            
            // Year of the record
            $year = $new_records[$i]['basic_information']['year'];
            
            
            // Record Rating
            $rating = $new_records[$i]['rating'];
            $rating_text = strval($rating);
            
            
            // Record Media Condition;
            if ($new_records[$i]['notes'][0]['value'] != 'Near Mint (NM or M-)'){
                $small_cond = explode(' ', $new_records[$i]['notes'][0]['value']);
                $media_text = end($small_cond);
            }
            else {
                $media_text = '(M-)';
            }
                
            
            // Record Sleeve Condition
            if ($new_records[$i]['notes'][1]['value'] != 'Near Mint (NM or M-)'){
                $small_cond = explode(' ', $new_records[$i]['notes'][1]['value']);
                $sleeve_text = end($small_cond);
                
            }
            else {
                $sleeve_text = '(M-)';
            }
            
            // Record Notes
            $notes_text = $new_records[$i]['notes'][2]['value'];
            $notes_text = str_replace("'","''",$notes_text);
            
            $bad_chars = array(' ', '-', '.', '$');
            $note_string = explode(' ', $new_records[$i]['notes'][2]['value']);
            $note_string = str_replace($bad_chars, '', $note_string[0]);
            
            // Attempt to assign a monetary value given the record's note
            try {
                $value = intval($note_string);         
            } catch(Exception $Z) {
                print_r($Z);
            }
    
            // Record Instance ID
            $instance_id = $new_records[$i]['instance_id'];
            
            // Record Vinyl ID
            $vinyl_id = $new_records[$i]['id'];
            
            $title_label_text = $title_text . $label_text;
            $title_label_text = str_replace("'","''",$title_label_text);
            $artist_text = str_replace("'","''",$artist_text);
            
            // Push all of that into a SQL query 
            $sql = "INSERT INTO records (artist, title_label, format, year, rating, media_cond, sleeve_cond, notes, value, instance_id, vinyl_id) VALUES ('" . $artist_text . "','" . $title_label_text . "','" . $formats_text . "','" . strval($year) . "','" . $rating_text . "','" . $media_text . "','" . $sleeve_text . "','" . $notes_text . "','" . $value . "','" . $instance_id . "','" . $vinyl_id . "')";
            if (mysqli_query($conn, $sql)) {
                echo "New record created successfully!<br>";
            } else {
                echo "Error: " . $sql . "<br>" . mysqli_error($conn) . "<br>";
            }
        }
        
        
        if(  !isset($_GET["sort"]) || ( isset($_GET["sort"]) && $_GET["sort"] != "datesort")  )
            $sql = "SELECT id, artist, title_label, format, year, rating, media_cond, sleeve_cond, notes, value, instance_id, vinyl_id FROM records ORDER BY value DESC";
        else 
            $sql = "SELECT id, artist, title_label, format, year, rating, media_cond, sleeve_cond, notes, value, instance_id, vinyl_id FROM records ORDER BY instance_id DESC";

        $result = mysqli_query($conn, $sql);
        unset($db_records);
        $db_records = array();
        
        if (mysqli_num_rows($result) > 0) {
            // output data of each row
            while($row = mysqli_fetch_assoc($result)) {
                $db_records[] = $row;
            }
        } else {
            echo "0 results";
        }
        
    }
    
    // User clicked the Update Collection button, but there were no new records in the discogs collection
    else {
        echo '<br>No new records found<br>';
    }
    
}

// If the user has manually set how many records should be displayed per page
if( isset($_GET["page"]) && isset($_GET["per_page"]) ) {
    $page = $_GET["page"];
    $per_page = $_GET["per_page"];           
}

// Otherwise default to page 1, 25 records per page
else {
    $page = 1;
    $per_page = 25;
}

$actual_per_page = $per_page;

// If the user is attempting to search for records from a particular artist ...

if( isset($_GET["artist"]) && $_GET["artist"] != "" ){
    $artist_query = $_GET["artist"];
    $artist_query = str_replace("'","''",$artist_query);
    $artist_releases = array();
    $sql = "SELECT id, artist, title_label, format, year, rating, media_cond, sleeve_cond, notes, value, instance_id, vinyl_id FROM records WHERE artist='" . $artist_query . "' ORDER BY value DESC";
    $result = mysqli_query($conn, $sql);
    unset($db_records);
    $db_records = array();
    
    if (mysqli_num_rows($result) > 0) {
        // output data of each row
        while($row = mysqli_fetch_assoc($result)) {
            $db_records[] = $row;
        }
    } else {
        echo "0 results";
    }      
    
    if( (count($db_records) - (($page - 1) * 25)) < 25){
        $actual_per_page = $per_page;
        $per_page = count($db_records) - (($page - 1) * 25);
    }
}
?>
<center><img src="collection_logo.png"></center><br><hr><br>
<div class="container">
<h1 style="float:left">Records</h1>
<h2 style="float:right"><a href="http://lollookup.com/index.php?update=true">Update Collection</a> | <a href="http://lollookup.com/about.html">About</a></h2>
<br><br><br>

<table class="table">
  <thead>
    <tr>
      <th>Artist - Title ( Label(s) - Catalog#(s) )</th>
      <th>Format</th>
      <th>Year</th>
      <th>Rating</th>
      <th>Media Condition</th>
      <th>Sleeve Condition</th>
      <th>Notes (Total Value: $' . $total_collection_value . ')</th>
    </tr>
  </thead>
<tbody>

<?php  

// Populate the table with the necessary amount of records

for($i = (($page - 1) * $per_page); ($i < ($per_page * ($page))) && ($i < count($db_records)); $i++) {
    
    echo '<tr>';
    
    // Artist(s), Title, Label(s)
    echo '<td>';
    $artist_title_label = $db_records[$i]['artist'] . $db_records[$i]['title_label'];
    echo $artist_title_label;
    echo '</td>';
    
    // Format
    echo '<td>';
    echo $db_records[$i]['format'];
    echo '</td>';
    
    // Year
    echo '<td>';
    echo $db_records[$i]['year'];
    echo '</td>';
    
    // Rating
    echo '<td>';
    $rating = intval($db_records[$i]['rating']);
    for($r = 0; $r < $rating; $r++) {
        echo '&#9733';
    }
    for($r = 0; $r < 5 - $rating; $r++) {
        echo '&#9734';
    }
    echo '</td>';
    
    // Media Condition
    echo '<td>';
    echo $db_records[$i]['media_cond'];
    echo '</td>';
    
    // Sleeve Condition
    echo '<td>';
    echo $db_records[$i]['sleeve_cond'];
    echo '</td>';
    
    // Notes
    echo '<td>';
    echo $db_records[$i]['notes'];
    echo '</td>';
    
    echo '</tr>';
}

echo '</table><hr>';
    
?>
 <!-- Graphs -->
    <form  method="LINK" action="index.php?" style="float:left">
        <b><i><?php Print(count($db_records)); ?></b> Total Records</i> <span style="position:relative;right:-10px;"> <b>Page #</b>: <input type="text" name="page" value=<?php Print($page);?> size="2" maxlength="2"> 
         - <b>Per Page</b>: <input type="text" name="per_page" value=<?php Print($actual_per_page);?> size="2" maxlength="3"></span> | 
        
        <span id="moveright">
        Artist Search: <input type="text" name="artist" value=<?php 
        if(isset($_GET["artist"])){ 
            Print('"' . $_GET["artist"] . '"');
        }
        else 
            Print('""');
        ?> size="20" maxlength="40"></span>
        
        <span style="position:relative;right:-30px;"> 
         <input type="checkbox" name="sort" value="datesort"
         <?php
             if( isset($_GET["sort"]) && $_GET["sort"] == "datesort")
                 Print(" checked");
         ?>
        >   Sort by Date Added</span>
        
        <input style="position:relative;right:-50px;" type="submit" value="Submit">
    </form>
    <br>

<?php

// 
$record_conditions = array( "(M)" => 0, "(M-)" => 0, "(VG+)" => 0, "(VG)" => 0, "(G+)" => 0, "(G)" => 0, "(F)" => 0, "(P)" => 0);
$eras = array("50s" => 0,"60s" => 0,"70s" => 0,"80s" => 0,"90s" => 0,"00s" => 0);

for($x = 0; $x < count($db_records); $x++) {
    $record_conditions[($db_records[$x]['media_cond'])] += 1;
    $year = intval($db_records[$x]['year']);
    if( $year > 1950 && $year < 2010)
        $eras[(substr(strval($year), -2, 1) . '0s')] += 1;
} 



echo '<hr><h1>Collection Graphs </h1><i><span style="position:relative;right:-15px;">Mouse over slices for details.</span></i>';
echo '</div>';

} catch(Exception $E) {
print_r($E);
}
?>

<!--Load the AJAX API-->
<!-- !!! The (majority of the) following code was snipped from a google API tutorial !!! -->
<!-- More information can be found here: https://developers.google.com/chart/interactive/docs/gallery/piechart -->

    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">

      // Load the Visualization API and the piechart package.
      google.load('visualization', '1.0', {'packages':['corechart']});

      // Set a callback to run when the Google Visualization API is loaded.
      google.setOnLoadCallback(drawChart);

      // Callback that creates and populates a data table,
      // instantiates the pie chart, passes in the data and
      // draws it.
      function drawChart() {

        // Create the Conditions table.
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Condition');
        data.addColumn('number', 'Records');
        data.addRows([
          ['(M)', <?php Print($record_conditions['(M)']); ?>],
          ['(M-)', <?php Print($record_conditions['(M-)']); ?>],
          ['(VG+)', <?php Print($record_conditions['(VG+)']); ?>],
          ['(VG)', <?php Print($record_conditions['(VG)']); ?>],
          ['(G+)', <?php Print($record_conditions['(G+)']); ?>],
          ['(G)', <?php Print($record_conditions['(G)']); ?>],
          ['(F)', <?php Print($record_conditions['(F)']); ?>],
          ['(P)', <?php Print($record_conditions['(P)']); ?>]
        ]);

        // Set chart options
        var options = {'title':'Record Condition Distribution',
                       'width':500,
                       'height':374,
                       fontSize:16,
                       pieSliceText:'label',
                       pieSliceTextStyle: {
                           color: 'black',
                           fontSize: 16
                       }
        };

        // Instantiate and draw our chart, passing in some options.
        var chart = new google.visualization.PieChart(document.getElementById('condition_chart_div'));
        chart.draw(data, options);
        
        
        // Create the Eras table.
        var dataE = new google.visualization.DataTable();
        dataE.addColumn('string', 'Era');
        dataE.addColumn('number', 'Records');
        dataE.addRows([
          ['50s', <?php Print($eras['50s']); ?>],
          ['60s', <?php Print($eras['60s']); ?>],
          ['70s', <?php Print($eras['70s']); ?>],
          ['80s', <?php Print($eras['80s']); ?>],
          ['90s', <?php Print($eras['90s']); ?>],
          ['00s', <?php Print($eras['00s']); ?>]
        ]);

        // Set chart options
        var optionsE = {'title':'Record Vintage Distribution',
                       'width':500,
                       'height':374,
                       fontSize:16,
                       pieSliceText:'label',
                       pieSliceTextStyle: {
                           color: 'black',
                           fontSize: 16
                       }
        };

        // Instantiate and draw our chart, passing in some options.
        var chartE = new google.visualization.PieChart(document.getElementById('era_chart_div'));
        chartE.draw(dataE, optionsE);
       
      }
      
    </script>
<center>
<div style="width:75%; height:600">
<span id="condition_chart_div", style="float:left"></span> <span id="era_chart_div", style="float:right"></span>
</div>
</center>

</body>
</html>
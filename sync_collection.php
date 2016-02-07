<?php
ini_set('display_startup_errors',1);
ini_set('display_errors',1);
error_reporting(-1);

session_start();

$consumer_key = "mtWMTxjQBYByRHBxKNjF";
$consumer_secret = "KXzAmIXAEDVGhyzLiuMvTrvPgLUEdbtm";
$request_token_path = "https://api.discogs.com/oauth/request_token";
$scope = 'http://api.discogs.com';
//$scope = 'http://www.discogs.com/oauth/authorize';

require 'oauth.php';

$oauthObject = new OAuthSimple();
$scope = 'http://api.discogs.com';

if(isset($_GET['clean'] ) ) $_SESSION['state'] = 0;

$signatures = array( 'consumer_key' => $consumer_key,
'shared_secret' => $consumer_secret);

if (!isset($_SESSION['state']))
    $_SESSION['state'] = 1;

if(!isset($_GET['oauth_token']) && isset($_SESSION['state']) && $_SESSION['state']==1) $_SESSION['state'] = 0;

if(!isset($_GET['oauth_token']) && !$_SESSION['state']) {

    $result = $oauthObject->sign(array(
    'path' =>'http://api.discogs.com/oauth/request_token',
    'parameters'=> array(
    'scope' => $scope,
    'oauth_callback'=> 'http://lollookup.com'),
    'signatures'=> $signatures));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, 'discogs_api_example/1.1 +http://lollookup.com');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $result['signed_url']);

    $r = curl_exec($ch);

    curl_close($ch);

    // Then we parse the string for the request token and the matching token secret.
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
    //header('Location: '.$authurl.'?oauth_token='.$request_token_info['oauth_token']);
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

    // ... and get the web page and store it as a string again.
    $ch = curl_init();
    //Set the User-Agent Identifier
    curl_setopt($ch, CURLOPT_USERAGENT, 'discogs_api_example/1.1 +http://lollookup.com');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
    $r = curl_exec($ch);
    curl_close($ch);

    // parse the string to get you access token
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
        
    $params['path'] = "$scope/users/chuckblairtx/collection/folders/0";
    $params['signatures'] = $oauth_props;
    $params['parameters'] = '';
    $result = $oauthObject->sign($params);
    $url = $result['signed_url'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, 'discogs_api_example/1.1 +http://myweb.edu/mblair');
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //Execute the curl session
    $resp = curl_exec($ch);
    curl_close($ch);
    $parsed_resp = json_decode($resp, true);
    $num_coll_records = $parsed_resp['count'];
    echo '<br>Number of Records in Collection: ' . $num_coll_records . '<br>';
    $pages = intval($num_coll_records / 100);
    $extras = $num_coll_records - ($pages * 100);
    echo 'Pages: ' . strval($pages) . ', Extras: ' . strval($extras);
    
    $all_records = array();

    $servername = "localhost";
    $username = "useradmin1";
    $password = "Bla1992Ir";
    $dbname = "recordsdb";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
        echo "<br><br>died a cruel death<br><br>";
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT id, artist, title_label, format, year, rating, media_cond, sleeve_cond, notes, value, instance_id, vinyl_id FROM records ORDER BY value DESC";

    $result = mysqli_query($conn, $sql);
    $db_records = array();
    
    if (mysqli_num_rows($result) > 0) {
        // output data of each row
        while($row = mysqli_fetch_assoc($result)) {
            $db_records[] = $row;
        }
    } else {
        echo "0 results";
    }
    
    
    
for($p = 1; $p <= ($pages + 1); $p++) {
    
    $oauthObject->reset();
        
    // rebuild it with the URL of the resource you want to access and the token/secret
    $params['path'] = "$scope/users/chuckblairtx/collection/folders/0/releases";
    $params['signatures'] = $oauth_props;
    
    // change both
    if( isset($_GET['q']) ) $params['parameters'] = 'page=' . $p . '&per_page=100&sort=added&sort_order=desc';
    else $params['parameters'] =                    'page=' . $p . '&per_page=100&sort=added&sort_order=desc';
    $result = $oauthObject->sign($params);
    $url = $result['signed_url'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, 'discogs_api_example/1.1 +http://myweb.edu/mblair');
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //Execute the curl session
    $output_updated = curl_exec($ch);
    curl_close($ch);
    
    
    $parsed_json_updated = json_decode($output_updated, true);
    $found_in_db = false;
    $new_records = array();
    
    $records_on_page = 100;
    if($p == ($pages + 1))
        $records_on_page = $extras;
        
    for($i = 0; $i < $records_on_page; $i++) {
        $all_records[] = $parsed_json_updated['releases'][$i];
        //echo $parsed_json_updated['releases'][$i]['basic_information']['title'] . "<br>";
        for($j = 0; $j < count($db_records); $j++){
        
            if($parsed_json_updated['releases'][$i]['instance_id'] == $db_records[$j]['instance_id']) {
                
                $found_in_db = true;
                $real_note = $parsed_json_updated['releases'][$i]['notes'][2]['value'];
                
                if($real_note != $db_records[$j]['notes']) {
                    // Notes
                    $real_note = str_replace("'","''",$real_note);
    
                    $bad_chars = array(' ', '-', '.', '$');
                    $note_string = explode(' ', $real_note);
                    $note_string = str_replace($bad_chars, '', $note_string[0]);
                    try {
                        $value = intval($note_string);         
                    } catch(Exception $Z) {
                        print_r($Z);
                    }
                    echo "New Value for " . $parsed_json_updated['releases'][$i]['basic_information']['title'] . ": <b>" . strval($value) . "</b><br>";
                    echo "New Note for " .  $parsed_json_updated['releases'][$i]['basic_information']['title'] . ": <b>" . $real_note . "</b><br>";
                    echo "Old Note for " .  $parsed_json_updated['releases'][$i]['basic_information']['title'] . ": <b>" . $db_records[$j]['notes'] . "</b><br>";
                    
                    $sql = "UPDATE records SET notes='" . $real_note . "',value=" . strval($value) . " WHERE instance_id=" . strval($db_records[$j]['instance_id']);
                    $result = mysqli_query($conn, $sql);
                }
                break;
            }
        }
        
        
        $found_in_db = false;
    }
    }
    echo "<br>" . strval(count($all_records)) . "<br>";
    $found_in_coll = false;
    $non_existing = 0;
    for($i = 0; $i < count($db_records); $i++) {
        
        for($j = 0; $j < count($all_records); $j++) {
            if($all_records[$j]['instance_id'] == $db_records[$i]['instance_id'])
                $found_in_coll = true;
        }
        
        if(!$found_in_coll) {
            echo "<br><b>" . $db_records[$i]['artist'] . $db_records[$i]['title_label'] . '[ ' . $db_records[$i]['notes'] . ' ]</b> is in the database but not the collection. <br>';
            $sql = "DELETE FROM records WHERE instance_id='" . $db_records[$i]['instance_id'] . "'";
            $result = mysqli_query($conn, $sql);
            if (mysqli_query($conn, $sql)) 
                echo "Successfully removed record from the database.<br>";
            else 
                echo "Error Deleting: " . $sql . "<br>" . mysqli_error($conn) . "<br>";
            $non_existing++;
        }
        $found_in_coll = false;
    }
    
    echo '<br>Total Non-Existing Records: ' . $non_existing . '<br>'; 

    ?>

<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
  <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
</head>
    
    
</html>
    
    
    
    
    
    
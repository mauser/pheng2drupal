<?php
 
ini_set("display_errors","2");

if(!($link = mysqli_connect ( "localhost" , "root" , "", "drupal" ))) {
    die("Keine Verbindung zur Datenbank.");
} else {
    print "verbindung klappt...";
}


function dateToTimestamp( $string  ){
	$format = 'Y-m-d H:i:s';
	$dt = DateTime::createFromFormat($format, $string);
	#echo "Format: $format; " . $dt->date . "\n";
	return $dt->getTimestamp();
}

function getTIDbyString( $string, $link ){

	$res = mysqli_query($link, "SELECT tid  FROM term_data WHERE name = '" . $string . "'");
        $vid_row = mysqli_fetch_row($res);
        return $vid_row[0];
}


function importPhengUser( $link )
{
	mysqli_query($link, "delete from users where uid > 5");
	$ergebnis = mysqli_query($link, "SELECT name,pass, email  FROM pheng_users");
	while($daten = mysqli_fetch_assoc($ergebnis)) {

    		$name = $daten["name"];
    		$mail = $daten["email"];
    		$pass = md5( $daten["pass"] );
	 
   		print "<br>$name";    
 		//echo "<br />Website: ".$daten["website"];
    		$sql = "INSERT INTO users VALUES('','" .  $name . "','" . $pass . " ','" . $mail . "','0','0','0','','','0','1271511383','0','0','1','NULL','" . $mail . "','','','')"; 

   		# print $sql;
   		#TODO: add user roles
		print "ROLE NOT SET!!";

   		if( mysqli_query($link,$sql)) {
      			echo "Erfolgreiches UPDATE.";
   		} else {
    			echo "UPDATE gescheitert.";
    			echo $link->error;
    			#exit();
		}

	}
}


function importPhengComments( $link )
{
	mysqli_query($link, "delete from comments where cid > 5");
	$ergebnis = mysqli_query($link, "SELECT *  FROM pheng_posts ORDER BY id ASC");
	while($daten = mysqli_fetch_assoc($ergebnis)) {

    		$d = $daten["post_date"];
		$timestamp = dateToTimestamp ( $d );    		

		$res = mysqli_query($link, "SELECT subject,minid  FROM pheng_threads WHERE id='" . $daten["thread"] ."' LIMIT 1");
		$vid_row = mysqli_fetch_row($res);
		$subject =  mysqli_real_escape_string($link, $vid_row[0] );

		$minid = $vid_row[1];
	
		if( $minid == $daten["id"])
		{
			#print "breaking here, same id!";
 			continue;
		}		

		$comment = mysqli_real_escape_string($link, $daten["message"] );
    		$poster = $daten["poster"];
		$name = $poster;
				
 
		$sql = "SELECT nid  FROM node_revisions WHERE title = '" . $subject . "'";
   		$res =  mysqli_query($link,$sql);
		if( $res ){
      			#echo "Erfolgreiches SELECT.";
   		} else {
    			echo "SELECT gescheitert.";
    			echo $link->error;
    			exit();
		}
		$vid_row = mysqli_fetch_row($res);
		$nid = $vid_row[0];
		if( $nid == 0){
			print $sql;
		}

		$sql = "SELECT nid  FROM comments WHERE nid='" . $nid . "'";
		$res = mysqli_query($link, $sql);
			
		$i=1;
		while( $rows = mysqli_fetch_array($res)){
			$i++;
		}
		$thread = str_pad($i, 2, "0", STR_PAD_LEFT). "/"; 	

	
		$res = mysqli_query($link, "SELECT uid  FROM users WHERE name='" . $daten["poster"] ."'");
		$vid_row = mysqli_fetch_row($res);
		$uid = $vid_row[0];


   		#print "<br>$name";    
 		#//echo "<br />Website: ".$daten["website"];
    		$sql = "INSERT INTO comments VALUES('','0','" .  $nid . "','" . $uid . " ','" . $subject . "','". $comment ."','127.0.0.1','" . $timestamp . "','0','1','". $thread . "','" . $name . "','mail','homepage')"; 

   		#print $sql;

   		if( mysqli_query($link,$sql)) {
      			#echo "Erfolgreiches UPDATE.";
   		} else {
    			echo "UPDATE gescheitert.";
    			echo $link->error;
    			exit();
		}

	}
}




function importPhengPosts( $link )
{

	print "deleting tables <br />";
	mysqli_query($link, "delete from node where nid > 5");
	mysqli_query($link, "delete from term_node where nid > 5");
	mysqli_query($link, "delete from node_revisions where nid > 5");
	mysqli_query($link, "delete from forum where nid > 5");
	mysqli_query($link, "delete from node_comment_statistics where nid > 5");
	#INSERT INTO `drupal`.`node_comment_statistics` (`nid`, `last_comment_timestamp`, `last_comment_name`, `last_comment_uid`, `comment_count`) VALUES ('6', '1271535921', 'NULL', '1', '0');
	print "importing posts <br />";
	////exit();	
	
	$ergebnis = mysqli_query($link, "SELECT *  FROM pheng_threads");
	while($daten = mysqli_fetch_assoc($ergebnis)) {

		$res = mysqli_query($link, "SELECT MAX(vid)  FROM node");
		$vid_row = mysqli_fetch_row($res);
		$vid = $vid_row[0]+1;

		$res = mysqli_query($link, "SELECT MAX(nid)  FROM node");
		$vid_row = mysqli_fetch_row($res);
		$nid = $vid_row[0]+1;


		$res = mysqli_query($link, "SELECT uid  FROM users WHERE name='" . $daten["starter"] ."'");
		$vid_row = mysqli_fetch_row($res);
		$uid = $vid_row[0];

		$res = mysqli_query($link, "SELECT uid  FROM users WHERE name='" . $daten["lastposter"] ."'");
		$vid_row = mysqli_fetch_row($res);
		$lastposter_uid = $vid_row[0];
		#print $uid;

		$minID = $daten["minid"];
		$replies = $daten["replies"];
		
		$res = mysqli_query($link, "SELECT message FROM pheng_posts WHERE id='" . $minID ."'");
		
		$vid_row = mysqli_fetch_row($res);
		$body = mysqli_real_escape_string( $link,  $vid_row[0]);



			
    		$lastChanged = dateToTimestamp( $daten["lastpost"] );
    		$created = dateToTimestamp( $daten["started"] );
		$title = mysqli_real_escape_string($link, $daten["subject"] );
		#$uid=32;
		#print $title;

		$sql = "INSERT INTO node VALUES('".  $nid . "','". $vid ."' , 'forum', '',  '" .  $title . "','" . $uid . "','1','" . $created . "','" . $lastChanged . "','2','0','0','0','0','0')"; 
		#print $sql;

   		if( mysqli_query($link,$sql)) {
      			#echo "Erfolgreiches UPDATE.<br />";
			#$res = mysqli_query($link, "SELECT nid  FROM node WHERE vid='" . $vid . "'");
                	#$vid_row = mysqli_fetch_row($res);
                	#$nid = $vid_row[0];

			$fid = $daten["fid"];
			if( $fid == 0 ) { $tid = getTIDbyString("Using hydrogen", $link); } 
			if( $fid == 1 ) { $tid = getTIDbyString("Using sound libraries", $link); } 
			if( $fid == 2 ) { $tid = getTIDbyString("Development questions",$link); } 
			if( $fid == 3 ) { $tid = getTIDbyString("Free zone",$link); } 
			if( $fid == 4 ) { $tid = getTIDbyString("Problems with hydrogen",$link); } 
			if( $fid == 5 ) { $tid = getTIDbyString("Hydrogen on Linux",$link); } 
			if( $fid == 6 ) { $tid = getTIDbyString("Hydrogen on Windows",$link); } 
			if( $fid == 7 ) { $tid = getTIDbyString("Hydrogen on OS X",$link); } 
			if( $fid == 8 ) { $tid = getTIDbyString("Share your songs",$link); } 
			if( $fid == 9 ) { $tid = getTIDbyString("Announcements",$link); } 
			if( $fid == 10 ) { $tid = getTIDbyString("Show your music",$link); } 

			#print "tid=". $tid . " vid= " . $vid ."nid=" . $nid;
			mysqli_query($link, "INSERT INTO forum  VALUES('" . $nid . "','" . $vid . "','" . $tid ."')" );
			
			if( mysqli_query($link, "INSERT INTO term_node  VALUES('" . $nid . "','" . $vid . "','" . $tid ."')" ) ) {
				# 
			} else {
    				echo $link->error;
			}

			$teaser  = $title ;

			$sql="INSERT INTO node_revisions VALUES('". $nid . "','". $vid . "','" . $uid . "','" . $title . "','" . $body . "','" . $teaser . "','','" . $created . "','1')";
			#print $sql;
			#mysqli_query( $link, $sql); 
			if( mysqli_query($link, $sql  ) ){ 
				# 
			} else {
    				echo $link->error;
			}

	 		$last_comment_timestamp = $lastChanged;
			$last_comment_name = "NULL";
			$last_comment_uid = $lastposter_uid;
			$comment_count = $replies;

			$sql ="INSERT INTO node_comment_statistics VALUES('" . $nid.  "','" . $last_comment_timestamp  . "','" . $last_comment_name . "','" . $last_comment_uid . "','" . $comment_count . "')";
			#print $sql;
			if( mysqli_query($link, $sql  ) ){ 
				# 
			} else {
    				echo $link->error;
			}
				
   		} else {
    			echo "UPDATE gescheitert.<br />";
    			echo $link->error;
    			exit();
		}

		


	}

}


#importPhengUser( $link );
#importPhengPosts( $link );
importPhengComments( $link );






?>



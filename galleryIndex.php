<?php
$thumbSize = "100x100"; //The size of thumbnails generated
$fontSize = 10; //The font size used on text thumbnails

$imageExts = array('.jpg','.jpeg','.png','.gif','.bmp','.ico','.svg','.tiff','.psd','.xcf');
$documentExts = array('.pdf','.doc','.html','.htm','.ps','.docx');
$textExts = array('.txt','.php','.cpp','.tex','.lua','.java','.js','.css');
$videoExts = array('.avi','.mp4','.mpeg','.mkv','wmv','flv');
$soundExts = array('.mp3','.wav','.ogg','.flac','.wma');
$archiveExts = array('.zip','.tar.gz','.rar','.bz2','.7z','.apk','.jar');
$thumbExt = ".thm.jpg";

$allExts = array_merge($imageExts, $documentExts, $textExts, $videoExts, $soundExts, $archiveExts);
$cleanDir = $_SERVER["REQUEST_URI"];
$dir = "..$cleanDir";
$thumbDir = ".thm/";

if(!is_dir("$dir$thumbDir"))
{
	mkdir("$dir$thumbDir");
	chmod("$dir$thumbDir", 0755);
}

function getMatchRegex($exts)
{
	if(is_array($exts))
	{
		foreach($exts as &$ext)
		{
			$ext = preg_quote($ext);
		}
		$matchString = implode('|',$exts);
	}
	else
	{
		$matchString = preg_quote($exts);
	}
	$matchString = '/(.*)('.$matchString.')$/i';
	return $matchString;
}

function getDirCount($path)
{
	$count = 0;
	$files = scandir($path);
	foreach($files as $file)
	{
		if(is_dir("$path$file") && !preg_match('/^\..*/',$file))
		{
			$count++;
		}
	}
	return $count;
}

function getFileCount($path)
{
	global $thumbExt;
	$count = 0;
	$files = scandir($path);
	foreach($files as $file)
	{
		if(!is_dir("$path$file") && !preg_match('/^\..*/i',$file) && !preg_match(getMatchRegex($thumbExt),$file))
		{
			$count++;
		}
	}
	return $count;
}

function listContents($path)
{
	$dir_count = getDirCount($path);
	$file_count = getFileCount($path);
	if($dir_count)
	{
		if($dir_count==1)
		{
			echo("$dir_count directory");
		}
		else
		{
			echo("$dir_count directories");
		}
		if($file_count)
		{
			echo(" and ");
		}
	}
	if($file_count)
	{
		if($file_count==1)
		{
			echo("$file_count file");
		}
		else
		{
			echo("$file_count files");
		}
	}
}

function getFileInfo($file, $extension)
{
	global $dir;
	global $imageExts;
	global $documentExts;
	global $textExts;
	global $videoExts;
	global $soundExts;
	global $archiveExts;

	global $thumbExt;
	global $thumbDir;

	$ext = strtolower($extension);

	//for image types
	if(in_array($ext, $imageExts))
	{
		list($width,$height)=getimagesize("$dir$file$extension");
		if($width&&$height)
		{
			echo("$width x $height ");
		}
		if($ext==".gif")
		{
			$mem_limit=preg_replace("/([0-9]*)M/i","\${1}000000",ini_get("memory_limit"));
			if((filesize("$dir$file$extension")+(memory_get_usage()))<$mem_limit)
			{
				$handle = fopen("$dir$file$extension",'rb');
				$data = fread($handle, filesize("$dir$file$extension"));
				fclose($handle);
				$frames = substr_count(bin2hex($data),'21f904');
				if($frames>1)
				{
					echo("$frames frames");
				}
			}
		}
		if($ext==".tiff")
		{
			$exifData = exif_read_data("$dir$file$extension");
			$dateTime = $exif_data['FileDateTime'];
			if($dateTime)
			{
				echo(date("d/m/y",$dateTime)." ");
			}
		}
		if($ext==".jpg")
		{
			$exif_data = exif_read_data("$dir$file$extension");
			$dateTime = $exif_data['FileDateTime'];
			if($dateTime)
			{
				echo(date("d/m/y",$dateTime)." ");
			}
		}
		if($ext==".svg")
		{
			$handle = fopen("$dir$file$extension",'rb');
			$data = fread($handle, filesize("$dir$file$extension"));
			fclose($handle);
			preg_match('/.*<svg[^>]*viewbox=["\']\d* \d* (\d*) (\d*)["\']/i', $data, $matches);
			if($matches)
			{
				echo($matches[1]." x ".$matches[2]);
			}
		}
	}

	//for document types
	elseif(in_array($ext, $documentExts))
	{
		if($ext==".pdf")
		{
			$handle = fopen("$dir$file$extension",'rb');
			$data = fread($handle, filesize("$dir$file$extension"));
			fclose($handle);
			preg_match_all("/\/Count\ ([0-9]*)/",$data,$results);
			$pages=end($results[1]);
			if($pages)
			{
				echo("$pages page");
				if($pages!=1)
				{
					echo("s");
				}
				echo("<br />");
			}
		}
	}

	//for text types
	elseif(in_array($ext, $textExts))
	{
		$lines = count(file("$dir$file$extension"));
		echo("$lines lines");
	}
}

function makeThumbnail($file, $extension)
{
	global $dir;
	global $imageExts;
	global $documentExts;
	global $textExts;
	global $videoExts;
	global $soundExts;
	global $archiveExts;

	global $thumbExt;
	global $thumbDir;
	global $thumbSize;
	global $fontSize;

	$ext = strtolower($extension);

	//for image types
	if(in_array($ext, $imageExts))
	{
		exec("convert $dir$file$extension\"[0]\" -resize $thumbSize \"$dir$thumbDir$file$extension$thumbExt\"");
		chmod("$dir$thumbDir$file$extension$thumbExt", 0755);
		return 1;
	}

	//for document types
	elseif(in_array($ext, $documentExts))
	{
		if($ext==".ps")
		{
			exec("convert $dir$file$extension\"[0]\" -resize $thumbSize \"$dir$thumbDir$file$extension$thumbExt\"");
			chmod("$dir$thumbDir$file$extension$thumbExt", 0755);
			return 1;
		}
		if($ext==".html" || $ext==".htm")
		{
			exec("html2ps -o $dir$thumbDir$file$extension.ps $dir$file$extension");
			exec("convert $dir$thumbDir$file$extension.ps\"[0]\" -resize $thumbSize \"$dir$thumbDir$file$extension$thumbExt\"");
			unlink("$dir$thumbDir$file$extension.ps");
			chmod("$dir$thumbDir$file$extension$thumbExt", 0755);
			return 1;
		}
		if($ext==".pdf")
		{
			exec("convert $dir$file$extension\"[0]\" -resize $thumbSize \"$dir$thumbDir$file$extension$thumbExt\"");
			chmod("$dir$thumbDir$file$extension$thumbExt", 0755);
			return 1;
		}
		if($ext==".doc")
		{
			exec("convert -size $thumbSize xc:white -pointsize $fontSize -draw \"gravity NorthWest text 0,0 {`catdoc \\\"$dir$file$extension\\\"`}\" \"$dir$thumbDir$file$extension$thumbExt\"");
			chmod("$dir$thumbDir$file$extension$thumbExt", 0755);
			return 1;
		}
		if($ext==".docx")
		{
			exec("convert -size $thumbSize xc:white -pointsize $fontSize -draw \"gravity NorthWest text 0,0 {`unzip -j -p \\\"$dir$file$extension\\\" word/document.xml|sed -re 's/<w:pStyle[^>]*>/\\n/g;s/<\\/w:tc>/    /g;s/<\\/w:tr>/\\n/g;s/<[^>]*>//g'`}\" \"$dir$thumbDir$file$extension$thumbExt\"");
			chmod("$dir$thumbDir$file$extension$thumbExt", 0755);
			return 1;
		}
	}

	//for video types
	elseif(in_array($ext, $videoExts))
	{
		exec("mplayer $dir$file$extension -ss 3 -nosound -vo jpeg:outdir=$dir$thumbDir -frames 1");
		exec("convert ".$dir.$thumbDir."00000001.jpg\"[0]\" -resize $thumbSize \"$dir$thumbDir$file$extension$thumbExt\"");
		unlink($dir.$thumbDir."00000001.jpg");
		chmod("$dir$thumbDir$file$extension$thumbExt", 0755);
		return 1;
	}

	//for sound types
	elseif(in_array($ext, $soundExts))
	{
		exec("convert -size $thumbSize -pointsize $fontSize -gravity NorthWest caption:\"`mplayer -nosound \\\"$dir$file$extension\\\" 2>/dev/null|grep -E 'Artist|Title|Album|Year|Comment|Track|Genre|AUDIO'`\" \"$dir$thumbDir$file$extension$thumbExt\"");
		chmod("$dir$thumbDir$file$extension$thumbExt", 0755);
		return 1;
	}

	//for text types
	elseif(in_array($ext, $textExts))
	{
		system("convert -size $thumbSize xc:white -pointsize $fontSize -draw \"gravity NorthWest text 0,0 {`head \\\"$dir$file$extension\\\"|sed -re 's/\\t/    /g'`}\" \"$dir$thumbDir$file$extension$thumbExt\"");
		chmod("$dir$thumbDir$file$extension$thumbExt", 0755);
		return 1;
	}

	//for archive types
	elseif(in_array($ext, $archiveExts))
	{
		if($ext==".tar.gz")
		{
			exec("convert -size $thumbSize xc:white -pointsize $fontSize -draw \"gravity NorthEast text 0,0 {`tar -tf \\\"$dir$file$extension\\\"|tail -n+10|awk 'NR%2!=0'`}\" \"$dir$thumbDir$file$extension$thumbExt\"");
			chmod("$dir$thumbDir$file$extension$thumbExt", 0755);
			return 1;
		}
		if($ext==".zip")
		{
			exec("convert -size $thumbSize xc:white -pointsize $fontSize -draw \"gravity NorthEast text 0,0 {`unzip -v \\\"$dir$file$extension\\\"|tail -n+4`}\" \"$dir$thumbDir$file$extension$thumbExt\"");
			chmod("$dir$thumbDir$file$extension$thumbExt", 0755);
			return 1;
		}
		if($ext==".rar")
		{
			exec("convert -size $thumbSize xc:white -pointsize $fontSize -draw \"gravity NorthEast text 0,0 {`unrar --list \\\"$dir$file$extension\\\"|tail -n+10|awk 'NR%2!=0'`}\" \"$dir$thumbDir$file$extension$thumbExt\"");
			chmod("$dir$thumbDir$file$extension$thumbExt", 0755);
			return 1;
		}
		if($ext==".apk")
		{
			exec("convert -size $thumbSize xc:white -pointsize $fontSize -draw \"gravity NorthEast text 0,0 {`unzip -v \\\"$dir$file$extension\\\"|tail -n+4`}\" \"$dir$thumbDir$file$extension$thumbExt\"");
			chmod("$dir$thumbDir$file$extension$thumbExt", 0755);
			return 1;
		}
		if($ext==".jar")
		{
			exec("convert -size $thumbSize xc:white -pointsize $fontSize -draw \"gravity NorthEast text 0,0 {`unzip -v \\\"$dir$file$extension\\\"|tail -n+4`}\" \"$dir$thumbDir$file$extension$thumbExt\"");
			chmod("$dir$thumbDir$file$extension$thumbExt", 0755);
			return 1;
		}
	}
	return 0;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
		<link rel="stylesheet" type="text/css" href="/system/gallery.css"/>
<?php
	echo("\t\t\t<title>$cleanDir</title>\n");
?>
	</head>
	<body>
		<h1><?php echo($cleanDir); ?></h1>
<?php
	$files = scandir($dir);
?>
		<p><?php listContents($dir); ?><br /></p>
<?php
	//list directories at the start
	foreach($files as $file)
	{
		if(is_dir("$dir$file"))
		{
			if($file == '..')
			{
				echo("\t\t<div class='text'>\n");
				echo("\t\t\t<a href=\"./$file\">Up a level</a>\n");
				echo("\t\t</div>\n");
			}
			//show contents of directories
			elseif(!preg_match('/^\..*/i',$file))
			{
				echo("\t\t<div class='text'>\n");
				echo("\t\t\t<a href=\"./$file\">$file<br />\n\t\t\t\t<span>");
				echo(listContents("$dir$file/"));
				echo("</span>\n\t\t\t</a>\n\t\t</div>\n");
			}
		}
	}

	//list normal files
	foreach($files as $file)
	{
		if(!is_dir("$dir$file")&& preg_match(getMatchRegex($allExts),$file) && !preg_match(getMatchRegex($thumbExt),$file) && !preg_match('/^\..*/i',$file))
		{
			$extension=preg_replace(getMatchRegex($allExts),'$2',$file);
			$file=preg_replace(getMatchRegex($allExts),'$1',$file);
			echo("\t\t<div class='thumb'>\n");
			echo("\t\t\t<a href=\"$file$extension\">");
			//echo("<span class='title'>$file$extension</span>");
			if(file_exists("$dir$thumbDir$file$extension$thumbExt"))
			{
				echo("\n\t\t\t\t<img src='./$thumbDir".addslashes("$file$extension$thumbExt")."' alt='$file$extension' />\n\t\t\t\t<span class='info'>");
			}
			else
			{
				if(!makeThumbnail($file, $extension))
				{
					echo("$file$extension\n\t\t\t\t<span class='info'>No Thumbnail<br />");
				}
				else
				{
					echo("\n\t\t\t\t<img src='./$thumbDir".addslashes("$file$extension$thumbExt")."' alt='$file$extension' />\n\t\t\t\t<span class='info'>");
				}
			}
			$words = preg_split("/ /", $file.$extension);
			foreach($words as &$word)
			{
				$word = wordwrap($word, 6, "&shy;", true);
			}
			echo(implode(" ", $words)."<br /><span>");
			getFileInfo($file, $extension);
			echo("</span>");
			echo("</span>\n");
			echo("\t\t\t</a>\n\t\t</div>\n");
		}
		elseif(!is_dir("$dir$file") && !preg_match('/^\..*/i',$file))
		{
			echo("\t\t<div class='text'>\n");
			echo("\t\t\t<a href=\"./$file\">$file<br />\n\t\t\t\t<span>");
			echo("</span>\n\t\t\t</a>\n\t\t</div>\n");
		}
	}
?>
	</body>
</html>

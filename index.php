<script src="bootstrap/js/jquery-3.3.1.slim.min.js" crossorigin="anonymous"></script>

<?php
if(isset($_POST['submit']) && $_FILES['file']['error'] === UPLOAD_ERR_OK)
{
	if(in_array($_FILES['file']['type'],array('text/xml')))
	{
		//initial settings
		ini_set('memory_limit', '10000000M');
		header('Content-Type: text/html; charset=utf-8');

		//import you default categories: tafseer,translation and types: ayas,pages and so on
		$cats = array('tafseer'=>array('aya_f'=>'Id','lang_f'=>'lang'),'translation'=>array('aya_f'=>'id','lang_f'=>'Lang'));
		$types = array('ayas'=>array('attr'=>''),'pages'=>array('attr'=>'Page'),'suras'=>array('attr'=>'S'),'juzas'=>array('attr'=>'Juz'),'hizps'=>array('attr'=>'Hzb'),'quarters'=>array('attr'=>'R'));

		//Get url parameters: cat and type
		if(isset($_POST['cat'],$_POST['type']) && key_exists($_POST['cat'],$cats) && key_exists($_POST['type'],$types))
		{
			$uploads = 'test/';
			if(move_uploaded_file($_FILES['file']['tmp_name'], $uploads.$_POST['cat'].'.'.pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION)))
			{
			
				//create directories depending on category and type
				if(!is_dir($uploads)) mkdir($uploads);
				if(!is_dir($uploads.$_POST['cat'])) mkdir($uploads.$_POST['cat']);
				if(!is_dir($uploads.$_POST['cat'].'/'.$_POST['type'])) mkdir($uploads.$_POST['cat'].'/'.$_POST['type']);

				//import the basic file aya xml
				$aya_all=simplexml_load_file($uploads.'aya.xml');
				$aya_array = json_decode(json_encode((array)$aya_all), TRUE);
				
				//import the basic file language xml
				$langs = array();
				$lang_all=simplexml_load_file($uploads.'language.xml');
				$lang_array = json_decode(json_encode((array)$lang_all), TRUE);
				foreach($lang_array['language'] as $lang) {	$langs[$lang[key($lang)]] = $lang['code']; }
				
				//load category xml file
				$cat_file=simplexml_load_file($uploads.$_POST['cat'].'.xml');

				foreach($aya_array as $item)
				{
					foreach($item as $key => $value)
					{
						$file_name = array(
							'ayas'=>$value[key($value)].'_'.$value['SA'].'_'.$value['Page'].'_'.$value['R'].'_'.$value['Hzb'].'_'.$value['Juz'],
							'pages'=>$value['Page'],
							'suras'=>$value['S'],
							'juzas'=>$value['Juz'],
							'hizps'=>$value['Hzb'],
							'quarters'=>$value['R']
						);
			
						if(!file_exists($uploads.$_POST['cat'].'/'.$_POST['type'].'/'.$file_name[$_POST['type']].'.xml'))
						{
							//create new xml file
							$myfile = fopen($uploads.$_POST['cat'].'/'.$_POST['type'].'/'.$file_name[$_POST['type']].'.xml', "w") or die("Unable to open file!");
							$txt = '<?xml version="1.0" encoding="UTF-8" ?><'.$_POST['type'].'_'.$_POST['cat'].'_info></'.$_POST['type'].'_'.$_POST['cat'].'_info>';
							fwrite($myfile, $txt);
							fclose($myfile);
						}
					}
				}

				$files =  array_values(array_diff(scandir($uploads.$_POST['cat'].'/'.$_POST['type']), array('..', '.')));	
				foreach($files as $file)
				{
					//load xml file for modification
					$do = 0;
					$addition=simplexml_load_file($uploads.$_POST['cat'].'/'.$_POST['type'].'/'.$file);

					if($_POST['type'] == 'ayas')
					{
						$arr = explode('_',$file);
						foreach($cat_file as $item)
						{
							if($item->{$cats[$_POST['cat']]['aya_f']} == $arr[0])
							{
								$child = $addition->addChild($_POST['type'].'_'.$_POST['cat']);
								foreach($item as $key => $value)
								{
									//add data
									//if($key == $cats[$_POST['cat']]['lang_f']) $child->addAttribute($key,htmlspecialchars($langs["$value"]));
									if($key == $cats[$_POST['cat']]['lang_f']) $child->addChild($key,htmlspecialchars($langs["$value"]));
									else $child->addChild($key,htmlspecialchars($value));
								}
								$do = 1;
							}
						}
					}
					else
					{
						$myaya = array();
						$arr = explode('.',$file);

						foreach($aya_array['aya'] as $aya)
						{
							if($arr[0] == $aya[$types[$_POST['type']]['attr']])
								$myaya[] = $aya[key($aya)];
						}
				
						foreach($cat_file as $item)
						{
							if(in_array($item->{$cats[$_POST['cat']]['aya_f']},$myaya))
							{
								$child = $addition->addChild($_POST['type'].'_'.$_POST['cat']);
								foreach($item as $key => $value)
								{
									//add data
									//if($key == $cats[$_POST['cat']]['lang_f']) $child->addAttribute($key,htmlspecialchars($langs["$value"]));
									if($key == $cats[$_POST['cat']]['lang_f']) $child->addChild($key,htmlspecialchars($langs["$value"]));
									else $child->addChild($key,htmlspecialchars($value));
								}
								$do = 1;
							}
						}
					}

					//saving updated xml file
					if($do) $addition->asXML($uploads.$_POST['cat'].'/'.$_POST['type'].'/'.$file);
				}

				unlink($uploads.$_POST['cat'].'.xml');
				if($do) { ?> <script type="text/javascript">$(document).ready(function(){ $('.messages').html('Done'); $('.messages').css('color','#28a745'); })</script> <?php }
				else { ?> <script type="text/javascript">$(document).ready(function(){ $('.messages').html('Wrong XML file Format'); $('.messages').css('color','#dc3545'); })</script> <?php }
			}
			else { ?> <script type="text/javascript">$(document).ready(function(){ $('.messages').html('File Uploading Process Error'); $('.messages').css('color','#dc3545'); })</script> <?php }
		}
		else { ?> <script type="text/javascript">$(document).ready(function(){ $('.messages').html('"Category" and "Type" Must Be Selected'); $('.messages').css('color','#dc3545'); })</script> <?php }
	}
	else { ?> <script type="text/javascript">$(document).ready(function(){ $('.messages').html('The Uploaded File Must Be an XML File'); $('.messages').css('color','#dc3545'); })</script> <?php }
}
?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Add Data To XML</title>
		<link rel="stylesheet" href="bootstrap/css/bootstrap.min.css" integrity="sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB" crossorigin="anonymous">
		<link rel="stylesheet" href="bootstrap/css/boot.css">
		<script src="bootstrap/js/bootstrap.min.js" integrity="sha384-smHYKdLADwkXOn1EmN1qk/HfnUcbVRZyYmZ4qpPea6sjB/pTJ0euyQp0Mk8ck+5T" crossorigin="anonymous"></script>
		<script type="text/javascript">
			$(document).ready(function(){
				$('#form').submit(function() {
					$('#loader').modal({show: true, backdrop: 'static', keyboard: false});
					//e.preventDefault();
				});
			});
		</script>
	</head>
	<body>
		<div class="container">
			<div class="row">
				<div class="col-md-3 myrow s_row">
					Split XML data file to small files according to the Category and Type.
				</div>
				<div class="col-md-5 myrow b_row">
					<center><div class="circlebig"><div class="circle"></div></div></center>
					<form id="form" action="" method="POST" enctype="multipart/form-data">
						
						<!-- Messages -->
						<div class="form-group messages">
						</div>

						<!-- Categories List -->
						<div class="form-group">
							<label for="cat">Category</label>
							<select name="cat" id="cat" class="form-control select">
								<option value="tafseer">Tafseer</option>
								<option value="translation">Translation</option>
							</select>
						</div>
						
						<!-- Types List -->
						<div class="form-group">
							<label for="type">Type</label>
							<select name="type" id="type" class="form-control select">
								<option value="ayas">Ayas</option>
								<option value="pages">Pages</option>
								<option value="suras">Suras</option>
								<option value="juzas">Juzas</option>
								<option value="hizps">Hizps</option>
								<option value="quarters">Quarters</option>
							</select>
						</div>
						
						<!-- XML File -->
						<div class="form-group">
							<label for="file-upload" class="custom-file-upload">Choose File</label>
							<input id="file-upload" type="file" name="file" />
						</div>
						
						<!-- Submit Form -->
						<div class="form-group">
							<center><input type="submit" value="Submit" id="submit" name="submit" class="submit">
						</div>

					</form>
				</div>
				<div class="col-md-3 myrow s_row">
					Thanks
				</div>
			</div>
		</div>
		<div id="loader" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="padding-top: 55px;">
			<img src="imgs/7.gif" id="gif" style="display: block; margin: 0 auto; width: 250px;">
		</div>
	</body>
</html>
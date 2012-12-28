<?php

/**
 * 
 * @author      Hennek (http://twitter.com/Hennek_)
 * @licence     Copyleft - GNU GPL
 * @name        UpData
 * @version     0.1
 * 
 * Ce système utilise PluPload (GPL v2), plus d'infos sur : http://plupload.com/
 * 
 *    _   _      ______      _        
 *   | | | |     |  _  \    | |       
 *   | | | |_ __ | | | |__ _| |_ __ _ 
 *   | | | | '_ \| | | / _` | __/ _` |
 *   | |_| | |_) | |/ / (_| | || (_| |
 *    \___/| .__/|___/ \__,_|\__\__,_|
 *         | |                        
 *         |_| App                       
 *   
 * 
 * 
 * Cette modeste application a pour but de vous permettre d'uploader rapidement des fichiers 
 * sur votre serveur web. Rapide, simple et facile à configurer. En effet, il vous suffit 
 * juste de vous rendre dans la partie 'Variable utilisateur' pour le configurer comme bon 
 * vous semble.
 * 
 * @TODO - il est possible d'aller plus loin :
 *  * création des dossiers auto
 * 
 */

session_start('UpData');

// -----------------------------------------------------------------------------
// VARIABLE UTILISATEUR

@ini_set('upload_max_filesize', '16M');               // Taille maximale d'un fichier à charger
@ini_set('max_execution_time', 0);                    // Temps max pour l'exécution d'un script
@ini_set('memory_limit', '512M');                     // Mémoire limite qu'un script est autorisé à allouer
define('PATH_UP', 'data/');                           // Path depuis la racine
define('INC', 'inc/');                                // Path où se situe les éléments
define('PLUPLOAD', 'inc/plupload/');                  // Path où se situe Plupload

include_once(INC . 'lang.updata.php');

$langue    = $en;                                     // Pour changer la langue : $fr ou $en -- To change the language, put $en
$pass      = 'coucou';                                // Mot de passe actuel
$allow_ext = array(                                   // Les extensions des fichiers autorisés
                'accdb',
                'java', 'docx', 'xlsx', 'html', 'pptx',
                'zip', 'pdf', 'doc', 'xls', 'odt', 'txt', 'htm', 'css', 'sql', 'swf', 'php', 'cbl', 
                'cfg', 'dat', 'ind', 'ini', 'bat', 'cpp', 'txt', 'ppt', 'vpp', 'vsd', 'lun', 'mp3', 'mp4', 'flv', 'avi', 'ddl',
                'sh', 'py', '7z', 'c',
                'jpg', 'png', 'gif', 'bmp', 'ico'
            );

// -----------------------------------------------------------------------------
// Configuration

// Config générale
header('Content-Type: text/html; charset=utf-8');   // Charset
date_default_timezone_set('Europe/Brussels');       // Timezone
@ini_set('magic_quotes_runtime', 0);                // Désactive la fonction magic_quotes
@ini_set('magic_quotes_gpc', '0');                  // Désactive la fonction magic_quotes

// Version
define('VERSION',      'v1.Alpha');
define('NAME_VERSION', 'UpData');

// Si le sysadmin a activé les magic quotes, ceci a pour effet de les désactivés
if (get_magic_quotes_gpc()) {
    function stripslashes_deep($value) { 
        $value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value); 
        return $value; 
    }

    $_POST   = array_map('stripslashes_deep', $_POST);
    $_GET    = array_map('stripslashes_deep', $_GET);
    $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
}

/**
 * Vérifie la version de PHP. Si la version est inférieure à 5.1.0, l'installation ne pourra pas se faire !
 */
function checkPHPVersion() {
    if (version_compare(PHP_VERSION, '5.1.0') < 0) {
        die('La version est obsolète ! Mettez à jour votre serveur avant de continuer. <br />
             This version of PHP is too old ! You have to upgrade your server before to continue.');
        exit();
    }
}

// -----------------------------------------------------------------------------
// Sécurité

// Création du token
if (!isset($_SESSION['tokens']))
    $_SESSION['tokens'] = array('key' => md5(time() . '-' . rand(0, 5000)));

// Vérification de la provenance du script. 
// S'il ne provient pas du serveur, on redirige l'internaute.
if ($_SERVER['PHP_SELF'] !== $_SERVER['SCRIPT_NAME']) {
    header('Location: index.php');
    exit();
}

// -----------------------------------------------------------------------------
// Divers

function cleanAccent($str) {
    $str = strtr($str,"ÀÁÂÃÄÅàáâãäåÇçÒÓÔÕÖØòóôõöøÈÉÊËèéêëÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ,",
    "AAAAAAaaaaaaCcOOOOOOooooooEEEEeeeeIIIIiiiiUUUUuuuuyNn_");
 
    $str = strtolower(trim($str)) ;
    $str = preg_replace('/[^a-z0-9\-\.,\*]/', '-', $str) ;
    $str = preg_replace('/([\-\.,\*]{2,})/ue', "substr('\\1', 0, 1)", $str) ;
    $str = preg_replace('/^[^a-z0-9]|[^a-z0-9]$/', '', $str ) ;
    $str = str_replace('.','-',$str);

    return ($str) ;
}

// -----------------------------------------------------------------------------
// TRAITEMENT

// --- VERIF PASS
if(isset($_GET['action']) && $_GET['action'] == "verif") {
    if(isset($_GET['token']) && $_GET['token'] == $_SESSION['tokens']['key']) {
        if($_GET['password'] == $pass) {
            die('{"error":false, "message":"OK"}');
            exit();
        }
        die('{"error":true, "message":"pass"}');
        exit();
    } else {
        die('{"error":true, "message":"token"}');
        exit();
    }
}

// --- DELETE
if(isset($_GET['action']) && $_GET['action'] == "delete") {
    if(isset($_GET['token']) && $_GET['token'] == $_SESSION['tokens']['key']) {
        unlink(PATH_UP . $_GET['file']);
        die('{"error":false, "message":"OK"}');
        exit();
    } else {
        die('{"error":true, "message":"token"}');
        exit();
    }
}


// --- UPLOAD
if(isset($_GET['action']) && $_GET['action'] == "upload") {
    if(empty($_FILES) || $_FILES['file']['error'] != 0) {
        die('{"error":true, "message":"<?php echo $langue[\'OP_FAILURE\']; ?>"}');
        exit();
    }

    $file = $_FILES['file'];
    $ext  = explode('.', $file['name']);
    $ext  = strtolower($ext[count($ext) - 1]);

    $name = substr($file['name'], 0, strlen($file['name']) - (strlen($ext)+1));
    $name = cleanAccent($name);
    $name .= '.' . $ext;

    // On uploade !
    if(in_array($ext, $allow_ext)){
        if(move_uploaded_file($_FILES['file']['tmp_name'], PATH_UP . $name)) {
            $link = PATH_UP . $name;
            $html = '<div class="file">' . basename($link) . '<div class="actions"><a href="'. $link . '" class="download">D</a> <a href="' . basename($link) . '" class="del">X</a></div></div>';
            $html = str_replace('"', '\\"', $html);

            die('{"error":false, "html":"' . $html . '"}');
            exit();
        }
        
        die('{"error":true, "message":"<?php echo $langue[\'OP_FAILURE\']; ?>"}');
        exit();
    }

    die('{"error":true, "message":"<?php echo $langue[\'OP_WARNING\']; ?>"}');
    exit();
}

?><!DOCTYPE html>
<html lang="fr-FR">
    <head>
        <meta charset="utf-8" />
        <title>UpData</title>
        <link rel="stylesheet" type="text/css" href="<?php echo INC; ?>style.css" />
    </head>
    <body>
        
        <div class="main">
            <h1><a href="">UpData</a></h1>
            <p><?php echo $langue['about']; ?></p>

            <div id="plupload">
                <div id="droparea">
                    <p><?php echo $langue['file']; ?></p>
                    <span class="or"><?php echo $langue['or']; ?></span>
                    <a href="#" id="browse" class="btn"><?php echo $langue['open']; ?></a>
                </div>

                <div id="filelist">
                    <?php foreach(glob(PATH_UP . '*.*') as $v): ?>
                        <div class="file">
                            <?php echo basename($v); ?>
                            <div class="actions">
                                <a href="<?php echo  $v; ?>" class="download">D</a>
                                <a href="<?php echo basename($v); ?>" class="del">X</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="clear">
            </div>
        </div>

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript"></script>
        <!--<script src="<?php echo INC; ?>jquery.min.js" type="text/javascript"></script>-->

        <script type="text/javascript" src="<?php echo PLUPLOAD; ?>plupload.js"></script>
        <script type="text/javascript" src="<?php echo PLUPLOAD; ?>plupload.html5.js"></script>
        <script type="text/javascript" src="<?php echo PLUPLOAD; ?>plupload.flash.js"></script>
        <script type="text/javascript">
            var uploader = new plupload.Uploader({
                runtimes  : 'html5, flash',
                container : 'plupload',
                browse_button : 'browse',
                drop_element : 'droparea',
                url : '?action=upload',
                flash_swf_url : '<?php echo PLUPLOAD; ?>',
                multipart : true,
                urlstream_upload : true
            });

            uploader.bind('Init', function(up, params) {
                if(params.runtime != "html5") {
                    $('#droparea').find('p, span').remove();
                    $('#droparea').find('#browse').css('position', 'absolute').css('top', '90px').css('left', '45%');
                }
            });

            uploader.bind('UploadProgress', function(up, file) {
                $('#' + file.id).find('.progress').css('width', file.percent + '%');
            });

            uploader.init();

            uploader.bind('FilesAdded', function(up, files) {
                var filelist = $('#filelist');

                for(var i in files) {
                    var file = files[i];
                    filelist.prepend('<div id="' + file.id + '" class="file">' + file.name + ' (' + plupload.formatSize(file.size) + ')' + '<div class="progressbar"><div class="progress"></div></div></div>');
                }

                $('#droparea').removeClass('hover');

                var pass = prompt("<?php echo $langue['TXT_PASS']; ?>","");
                $.get(
                    '', 
                    {action: 'verif', password:pass, token:'<?php echo $_SESSION['tokens']['key']; ?>'},
                    function(data) {
                        var response = $.parseJSON(data);
                        if(response.message == "OK" && response.error == false) {
                            uploader.start();
                        } else {
                            alert("<?php echo $langue['WRONG_PASS']; ?>");
                            $('#' + file.id).remove();
                        }
                        uploader.refresh();
                    }
                );
            });

            uploader.bind('Error', function(up, error) {
                alert("<?php echo $langue['OP_FAILURE']; ?>" . error.message);

                $('#droparea').removeClass('hover');
                uploader.refresh();
            });

            uploader.bind('FileUploaded', function(up, file, response) {
                data = $.parseJSON(response.response);
                if(data.error == true) {
                    alert(data.message);
                    $('#' + file.id).remove();
                } else {
                    $('#' + file.id).replaceWith(data.html);
                }
            });

            jQuery(function($) {
                $('#droparea').bind({
                    dragover : function(e) {
                        $(this).addClass('hover');
                    },
                    dragleave : function(e) {
                        $(this).removeClass('hover');
                    }
                });
                $('.del').live('click', function(e) {
                    e.preventDefault();
                    var elem = $(this);
                    if(confirm("<?php echo $langue['OP_CONFIRM_DEL']; ?>")) {
                        $.get('', {action: 'delete', file:elem.attr('href'), token:'<?php echo $_SESSION['tokens']['key']; ?>'});
                        elem.parent().parent().slideUp();
                    }
                    return false;
                });

            })
        </script>
    </body>
</html>
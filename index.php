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
 * sur votre serveur web. Rapide, simple et "facile" à configurer. En effet, il vous suffit 
 * juste de vous rendre dans la partie 'Variable utilisateur' pour modifier les paramètres
 * comme bon vous semble.
 * 
 * Un tout grand merci à Grafikart.fr, UpData est basé sur l'un de ses tutos.
 * 
 * Note : si vous souhaitez réutiliser ce script pour l'un de vos projets, je vous conseille
 * de retirer la partie qui se charge de demander le mot de passe de l'internaute et d'établir
 * un système de session. De nombreux tutoriels existent.
 * 
 * @TODO - il est possible d'aller plus loin :
 *  * Création du dossier "data"
 *  * Solution/hack ie ?!
 *  * Lors de la suppression : vérifier que le fichier existe bien. Info sinon
 *  * Lors de l'ajout : vérifier qu'il n'y a pas un fichier déjà présent avec ce nom. Renommer si nécessaire
 * 
 */

session_start('UpData');

// -----------------------------------------------------------------------------
// VARIABLE UTILISATEUR

@ini_set('upload_max_filesize', '16M');               // Taille maximale d'un fichier à charger
@ini_set('memory_limit', '125M');                     // Mémoire limite qu'un script est autorisé à allouer
@ini_set('max_execution_time', 0);                    // Temps max pour l'exécution d'un script
define('PATH_UP', 'data/');                           // Path où se situe les fichiers uploadés
define('PLUPLOAD', 'inc/plupload/');                  // Path où se situe les fichiers de Plupload
define('INC', 'inc/');                                // Path où se situe les éléments nécessaire à l'app

include_once(INC . 'core/lang.updata.php');           // Fichier langue

$langue    = $lang['fr'];                             // Pour changer la langue : 'fr' ou 'en' -- To change the language, put $en
$pass      = 'coucou';                                // Mot de passe actuel. Sensible à la casse
$allow_ext = array(                                   // Les extensions des fichiers autorisés. Attention aux formats comme sh, exe ou encore php
                'accdb', 'java', 'docx', 'xlsx', 'html', 'pptx', 'sh', 'py', '7z', 'c',
                'zip', 'pdf', 'doc', 'xls', 'odt', 'txt', 'htm', 'css', 'sql', 'swf', 'cbl', 
                'cfg', 'dat', 'ind', 'ini', 'bat', 'cpp', 'txt', 'ppt', 'vpp', 'vsd', 'lun', 
                'mp3', 'mp4', 'flv', 'avi', 'ddl', 'jpg', 'png', 'gif', 'bmp', 'ico'
            );

// -----------------------------------------------------------------------------
// Configuration

// Config générale
header('Content-Type: text/html; charset=utf-8');     // Charset
@ini_set('magic_quotes_runtime', 0);                  // Désactive la fonction magic_quotes
@ini_set('magic_quotes_gpc', '0');                    // Désactive la fonction magic_quotes

// Version de l'app
define('VERSION',      'v1.Alpha');
define('NAME_VERSION', 'UpData');

// Si le sysadmin a activé les magic quotes, ceci aura pour effet de les désactiver
if (get_magic_quotes_gpc()) {
    function stripslashes_deep($value) { 
        $value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value); 
        return $value; 
    }

    $_POST   = array_map('stripslashes_deep', $_POST);
    $_GET    = array_map('stripslashes_deep', $_GET);
    $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
}

// Vérification de la version de PHP. Si la version est inférieure à 5.1.0, l'installation ne pourra pas se faire !
function checkPHPVersion() {
    if (version_compare(PHP_VERSION, '5.1.0') < 0) {
        die('La version est obsolète ! Mettez à jour votre serveur avant de continuer. <br />
             This version of PHP is too old ! You have to upgrade your server before to continue.');
        exit();
    }
}

checkPHPVersion();

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

// Supprimer les accents de la chaîne passée en paramètre
function cleanAccent($str) {
    $str = strtr($str,"ÀÁÂÃÄÅàáâãäåÇçÒÓÔÕÖØòóôõöøÈÉÊËèéêëÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ,",
                      "AAAAAAaaaaaaCcOOOOOOooooooEEEEeeeeIIIIiiiiUUUUuuuuyNn_");
 
    $str = strtolower(trim($str)) ;
    $str = preg_replace('/[^a-z0-9\-\.,\*]/', '-', $str) ;
    $str = preg_replace('/([\-\.,\*]{2,})/ue', "substr('\\1', 0, 1)", $str) ;
    $str = preg_replace('/^[^a-z0-9]|[^a-z0-9]$/', '', $str ) ;
    $str = str_replace('.', '-', $str);

    return $str;
}

// -----------------------------------------------------------------------------
// TRAITEMENT

// --- VERIF PASS
// Vérification du mot de passe entré par l'utilisateur.
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
// Suppression du fichier passé dans l'URL
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
// Méthode permettant d'uplader le fichier sur le serveur.
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
        <link rel="stylesheet" type="text/css" href="<?php echo INC; ?>tpl/style.css" />
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
                    <?php foreach(glob(PATH_UP . '*.*') as $v): // Listing des fichiers ?>
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

        <footer>
            <?php echo $langue['footer']; ?> - <?php echo NAME_VERSION; ?> <?php echo VERSION; ?>
        </footer>

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript"></script>
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

                    var pass = prompt("<?php echo $langue['OP_CONFIRM_DEL']; ?> <?php echo $langue['TXT_PASS']; ?>","");
                    $.get(
                        '', 
                        {action: 'verif', password:pass, token:'<?php echo $_SESSION['tokens']['key']; ?>'},
                        function(data) {
                            var response = $.parseJSON(data);
                            if(response.message == "OK" && response.error == false) {
                                $.get('', {action: 'delete', file:elem.attr('href'), token:'<?php echo $_SESSION['tokens']['key']; ?>'});
                                elem.parent().parent().slideUp();
                            }
                            return false;
                        }
                    );
                });
            })
        </script>
    </body>
</html>
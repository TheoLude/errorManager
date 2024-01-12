<?php
/*
Licence et conditions d'utilisations-----------------------------------------------------------------------------

-English---------------------------------------------------------------------------------------------------------
Copyright (C) 2001  - AUTHOR ANDRE thierry

This library is free software; you can redistribute it and/or modify it under the terms of the GNU Lesser General
Public License as published by the Free Software Foundation; either version 2.1 of the License, or (at your option)
any later version.

This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more
details.

You should have received a copy of the GNU Lesser General Public License along with this library; if not, write to :

Free Software Foundation,
Inc., 59 Temple Place,
Suite 330, Boston,
MA 02111-1307, Etats-Unis.
------------------------------------------------------------------------------------------------------------------

-Français---------------------------------------------------------------------------------------------------------
ModeliXe est distribué sous licence LGPL, merci de laisser cette en-tête, gage et garantie de cette licence.
ModeliXe est un moteur de template destiné à être utilisé par des applications écrites en PHP.
ModeliXe peut être utilisé dans des scripts vendus à des tiers aux titres de la licence LGPL. ModeliXe n'en reste
pas moins OpenSource et libre de droits en date du 23 Août 2001.

Copyright (C) 2001  - Auteur ANDRE thierry

Cette bibliothèque est libre, vous pouvez la redistribuer et/ou la modifier selon les termes de la Licence Publique
Générale GNU Limitée publiée par la Free Software Foundation version 2.1 et ultérieure.

Cette bibliothèque est distribuée car potentiellement utile, mais SANS AUCUNE GARANTIE, ni explicite ni implicite,
y compris les garanties de commercialisation ou d'adaptation dans un but spécifique. Reportez-vous à la Licence
Publique Générale GNU Limitée pour plus de détails.

Vous devez avoir reçu une copie de la Licence Publique Générale GNU Limitée en même temps que cette bibliothèque;
si ce n'est pas le cas, écrivez à:

Free Software Foundation,
Inc., 59 Temple Place,
Suite 330, Boston,
MA 02111-1307, Etats-Unis.

Pour tout renseignements mailez à modelixe@free.fr ou thierry.andre@freesbee.fr
--------------------------------------------------------------------------------------------------------------------
*/

namespace eXtensia\errorManager;

class ErrorManager {

	var $errorCounter = Array(0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0);

	var $errorMessage = '';
	var $errorEscape = '';
	var $errorLog = '';
	var $errorAlarme = '';

	var $errorTrackingLevel = 1;
	var $numberError = 0;
	var $maxErrorReport = 0;

	var $errorManagerSystem = true;
	var $bool_php_track = false;

	//Constructeur-----------------------------------------------------
	function ErrorManager($errorManagerSystem = '', $level = '', $escape = '', $file = '', $alarme = ''){
		$this -> SetErrorSystem($errorManagerSystem);
		$this -> SetErrorLevel($level);
		$this -> SetErrorEscape($escape);
		$this -> SetErrorAlarme($alarme);
		$this -> SetErrorLog($file);


		$this -> bool_php_track = ini_get('track_errors');
		if ($this -> bool_php_track && $php_errormsg) $this -> ErrorTracker(6, $php_errormsg);
	}

	//Setting ErrorManager---------------------------------------------------

	function SetErrorSystem($arg = ''){
		if (constant('ERROR_MANAGER_SYSTEM') && ! $arg) $arg = constant('ERROR_MANAGER_SYSTEM');
		$this -> errorManagerSystem = $arg;

		if ($this -> errorManagerSystem != 'off') $this -> errorManagerSystem = true;
		else $this -> errorManagerSystem = false;
	}

	function SetErrorLevel($arg = ''){
		if (constant('ERROR_MANAGER_LEVEL') && ! $arg) $arg = constant('ERROR_MANAGER_LEVEL');
		if ($arg) $this -> errorTrackingLevel = $arg;
	}

	function SetErrorEscape($arg = ''){
		if (constant('ERROR_MANAGER_ESCAPE') && ! $arg) $arg = constant('ERROR_MANAGER_ESCAPE');
		if ($arg && ! $this -> SetErrorOut($arg)) $this -> errorEscape = '';
	}

	function SetErrorAlarme($arg = ''){
		if (constant('ERROR_MANAGER_ALARME') && ! $arg) $arg = constant('ERROR_MANAGER_ALARME');
		if ($arg) $this -> errorAlarme = $arg;
	}

	function SetErrorLog($arg = ''){
		if (constant('ERROR_MANAGER_LOG') && ! $arg) $arg = constant('ERROR_MANAGER_LOG');
		if ($arg) $this -> errorLog = $arg;
	}

	//Parametrage -----------------------------------------------------------

	function SetErrorLock($func){
		if (strtolower($func) == 'actived') $func = true;
		if (strtolower($func) == 'desactived') $func = false;

		$this -> errorManagerSystem = $func;
		return true;
	}

	function SetErrorOut($url){
		if (@is_file($url) || preg_match('http://', $url)) {
			$this -> errorEscape = $url;
			return true;
		}
		else return false;
	}

	//Gestionnaire -----------------------------------------------------------

	function ErrorTracker($warning, $message, $func = '', $file = '', $line = ''){

		if ($this -> bool_php_track && $php_errormsg) {
			$message = $php_errormsg;
			$warning = 6;
		}

		switch ($warning){
			case 1:
			$type = "Low warning";
			break;
			case 2:
			$type = "Warning";
			break;
			case 3:
			$type = "Notification";
			break;
			case 4:
			$type = "Error";
			break;
			case 5:
			$type = "Emergency break";
			break;
			case 6:
			$type = "System break - debuggage needed";
			break;
			default:
			$type = "Unknown error";
			$warning = 0;
		}

		$this -> numberError ++;
		if (++ $this -> errorCounter[$warning] > 0 && $warning > $this -> maxErrorReport) $this -> maxErrorReport = $warning;

		if ($this -> numberError > 1) $pre = "<li>";
		else $pre = "<ul><li>";

		$this -> errorMessage .= $pre.$type.' no '.$this -> errorCounter[$warning].' ';
		if ($func) $this -> errorMessage .= 'on <b>'.$func.'</b> ';
		if ($file) $this -> errorMessage .= 'in file <b>'.$file.'</b> ';
		if ($line) $this -> errorMessage .= 'on line <b>'.$line.'</b> ';

		$this -> errorMessage .= ': <br><ul><li><i>'.$message.'</i><br><br></ul>'."\n";
		$this -> ErrorChecker();
	}


	function ErrorChecker($level = '', $bool_comment = true){
		$bool_get = false;
		if ($level == 'GET') {
			$bool_get = true;
			$level = '';
		}
		if ($level == '') $level = $this -> errorTrackingLevel;

		if ($this -> maxErrorReport >= $level || $bool_get) {

			if ($this -> maxErrorReport >= $level) {
				if ($bool_comment){
					$message = '<h3>Phase d\'exploitation de l\'application.</h3>'."\r\n";

					if (defined('CST_USR_LOGIN')) $message .= '<b style="font-size:16"> Session utilisateur '.constant('CST_USR_LOGIN').' ';
					if (defined('CST_USR_NAME')) $message .= '<i style="font-size:12">['.constant('CST_USR_NAME');
					if (defined('CST_USR_SURNAME')) $message .= ' '.constant('CST_USR_SURNAME');
					$message .= "]</i></b><br />\r\n";

					$message .= 'Le '.date('<b>d/M/Y</b> &\a\g\r\a\v\e; H:i:s')."<br />\r\n".'ErrorManager report, l\'application a provoqu&eacute; '.$this -> numberError.' erreur'.(($this -> numberError > 1)?'s':'').', regardez ci-dessous pour '.(($this -> numberError > 1)?'les':'la').' corriger :'."\r\n<br>\r\n".$this -> errorMessage."\r\n</ul>";
					$message .= "Environnement SCRIPT_FILENAME : ".$_SERVER['SCRIPT_FILENAME']." <br>\r\n";
					$message .= "Environnement REQUEST_URI : ".$_SERVER['REQUEST_URI']." <br>\r\n";
					$message .= "Environnement QUERY_STRING : ".$_SERVER['QUERY_STRING']." <br>\r\n";
					$message .= "Environnement POST : ".print_r($_POST, true)." <br>\r\n";
				}
				else $message = $this -> errorMessage;
			}

			if ($this -> errorManagerSystem && ! $bool_get) {

				if ($this -> errorAlarme) {
					$tab = explode(',', $this -> errorAlarme);
					while (list($key, $val) = each($tab)){
						if (! preg_match('/^(.+)@(.+)\.(.+)$/s', $val)) {
							$message .= "<p style='color:red;'>Your ERROR_MANAGER_ALARME mails configurations has got a mistake and was disabled.</p>";
							$this -> errorAlarme = '';
						}
					}

					if ($this -> errorAlarme) {
						/*$headers = "From: ".$this -> errorAlarme."\n";
						$headers .= "Reply-To: ".$this -> errorAlarme."<".$this -> errorAlarme.">\n";
						$headers .= "X-Sender: <".$this -> errorAlarme.">\n";
						$headers .= "Return-Path: <".$this -> errorAlarme.">\n";
						$headers .= "Content-Type: text/plain; charset=iso-8859-1";

						$trans_tbl = get_html_translation_table (HTML_ENTITIES);
						$trans_tbl = array_flip($trans_tbl);
						$post = strtr(strip_tags($message), $trans_tbl);

						if (! @mail($this -> errorAlarme, '[ErrorManager][Alarm]', $post, $headers)) $message .= "<p><b>SYSTEM ERROR ON MAIL FUNCTION - Alert email can't be sent</b></p>";*/

						$trans_tbl = get_html_translation_table(HTML_ENTITIES);
						$trans_tbl = array_flip($trans_tbl);
						//$str_rapport = strtr(strip_tags($message), $trans_tbl);
						$str_rapport = $message; //[MOD Théo 21/08/2019]

						$obj_mailer = new phpMailer();
						$obj_mailer -> IsSMTP();
						$obj_mailer -> IsHTML(true);  //[MOD Théo 21/08/2019]
						$obj_mailer -> setMailHost(constant('MAIL_SMTP_SERVER'));
						$obj_mailer -> setSmtpPort(25);
						$obj_mailer -> setFromMail($this -> errorAlarme, 'Webmaster Extensia');
						$obj_mailer -> setMailSubject('[ErrorManager][Alarm]');
						$obj_mailer -> setMailBody($str_rapport);
						$obj_mailer -> AddAddress('alerte.informatique@deromafrance.com');

						if (! $obj_mailer -> mailSend()){
							$message .= "\r\n<p><b>SYSTEM ERROR ON MAIL FUNCTION - Alert email can't be sent</b></p>\r\n";
						}
					}
				}

				if ($this -> errorLog) {
					$ouv = @fopen($this -> errorLog, 'a');
					@fputs($ouv, date('d/M/Y H:i:s')." -- \r\n".trim(strip_tags($this -> errorMessage))."\r\n");
					@fclose($ouv);
				}

				if ($this -> errorEscape) {
					if (! @header('location: '.$this -> errorEscape.'?str_erreur='.urlencode($message))) print($message);
					exit;
				}
				else {
					print($message);
					exit;
				}
			}
			else {
				if ($bool_get) return $message;
				else return false;
			}
		}
		else return true;
	}
}

?>
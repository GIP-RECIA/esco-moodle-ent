<?php // $Id: moodle-ent.php,v 0.2 2010/12/10 $

/**
 * modifie rapidement par Patrick Pollet
 ( pp@patrickpollet.net)
 * (c) INSA de Lyon Avril 2012
 * pour etre utilisable avec Moodle 2.X
 */
// Modifie le 19 mars 2014 pour ameliorations affichage et requetes - CD, apres odifications effectuees par KFN

require_once('../config.php');
require_once($CFG->libdir .'/filelib.php');

// Fonction permettant d'afficher les cours
function display_courses($courses, $detail) {
    if (empty($courses)) {
	// FIXME : cette fonction genere une fatal error tjrs car elle n'existe plus au sein de moodle 
	// TODO : A remplacer 
	// 
        print_simple_box(get_string('nocourses','my'),'center');
    } else {
        RUNN_print_overview($courses, $detail);
    }
}


// Fonction permettant d'afficher les activites recentes d'un cours
function display_recent_activities($activities) {
    global $CFG;

    foreach($activities as $activity) {
        // Affichage du cours concerne par les modifications
        $course_name = $activity['course']->fullname;
        $course_id = $activity['course']->id;
        $course_url = "course/view.php?id=$course_id";
        print('<dt class="course">' . html_link($course_url, $course_name) . '</dt>');

        // Recuperation et affichage des infos concernant le module modifie
        // INFO : Mis en commentaire car peut necessiter trop de temps a s'executer
        //        pour les personnes ayant beaucoup de cours
        /*
        $logs = $activity['logs'];
        foreach($logs as $log) {
            $module         = $log->module;
            $module_info_id = $log->info;
            $module_url     = 'mod/' . $module . '/' . $log->url;
            $module_info = get_module_info($module, $module_info_id);
            $module_name = $module_info->name;
                echo '<dd class="course">' . html_link($module_url, $module_name) . '</dd>';
        }
        */
    }

}

// Fonction permettant de recuperer les cours dans lesquels l'utilisateur joue un role
function get_enrolled_courses($username) {
    global $DB;

    // Preparation de la requete
    $sql  = 'SELECT distinct c.id , c.shortname , c.fullname , ula.timeaccess , c.sortorder , cat.path';
    $sql .= ' FROM mdl_course c';
    $sql .= ' LEFT JOIN (mdl_role_assignments ra, mdl_context x, mdl_user u, mdl_user_lastaccess ula, mdl_course_categories cat)';
    $sql .= ' ON u.id=ula.userid AND c.id=ula.courseid WHERE u.id = ra.userid AND x.id = ra.contextid AND cat.id = c.category';
    $sql .= ' AND u.username = ? AND x.instanceid = c.id AND x.contextlevel= 50';

    // Execution de la requete
    $courses = $DB->get_records_sql($sql, array($username));

    return $courses;
}

// Fonction permettant de recuperer les logs des modules modifies depuis
// la date specifiee
function get_course_mod_log($course_id, $time) {
    global $DB;

    // Recuperation des modules modifies
    $table  = 'log';
    $select = "course = $course_id AND time >= $time AND module <> 'label' AND action IN ('add','update') AND cmid <> 0";
    $logs   = $DB->get_records_select($table, $select, null, '', 'module, url, info');

    return $logs;
}
 
// Fonction permettant de recuperer les logs des modules modifies recemment
// dans les cours
function get_courses_mod_logs($courses) {
    global $DB;

    // Tableau assoicant les cours et leurs activites recentes
    $mod_logs_by_courses = array( );
    foreach($courses as $course) {
        // Recuperation du dernier acces utilisateur
        $last_access = $course->timeaccess;

        // Recuperation des changements effectues dans le cours depuis le dernier acces
        $course_id = $course->id;
        $course_logs = get_course_mod_log($course_id, $last_access);
        if($course_logs == null) {
            continue;
        }
        
        // Association du cours et des modifications
        $mod_logs_by_courses[$course_id] = array( 'course' => $course, 'logs' => $course_logs ); 
    }
    return $mod_logs_by_courses;
}

// Fonction permettant de recuperer les cours en fonction de la
// methode d'acces activee et de la presence, ou non, du mot de passe
function get_enrol_course( $enrol_mode, $with_password ) {
    global $DB;

    // Recuperation des cours dans lesquels la methode est activee (status = 0), entre autres...
    $sql  = 'SELECT c.id , c.shortname , c.fullname';
    $sql .= ' FROM mdl_course c, mdl_enrol e';
    $sql .= ' WHERE e.enrol = \'' . $enrol_mode . '\' AND e.status = 0';
    // Cours avec ou sans mot de passe ?
    if( $with_password ) {
        $sql .= ' AND e.password IS NOT NULL AND e.password <> \'\'';
    } else {
        $sql .= ' AND (e.password IS NULL OR e.password = \'\')';
    }
    $sql .= ' AND c.visible = 1 AND e.courseid = c.id';
    $sql .= ' ORDER BY c.fullname';

    //Execution de la requete
    $courses = $DB->get_records_sql($sql);

    return $courses;

}

// Fonction permettant de recuperer les cours avec l'acces anonyme active
function get_guest_enrol_course() {
    // Tous les cours en acces anonyme possedent un mot de passe en BD
    return get_enrol_course( 'guest', false );
}

// Fonction permettant de recuperer les informations sur une entree de module
function get_module_info($table, $id) {
    global $DB;

    // Recuperation du nom de l'entree dans la table du module
    $select = "id = $id";
    $module_info = $DB->get_record_select($table, $select, null, 'name');

    return $module_info;
}

// Fonction permettant de recuperer les cours avec l'auto inscription active
// Auto inscription avec cle d'inscription
function get_self_enrol_course_with_password() {
    return get_enrol_course( 'self', true );
}
//
// Fonction permettant de recuperer les cours avec l'auto inscription active
// Auto inscription sans cle d'inscription
function get_self_enrol_course_without_password() {
    return get_enrol_course( 'self', false );
}

// Fonction permettant de generer un lien HTML pour Moodle
// Modififcation pour que le lien generer soit en relatif 
// ! N'a pas l'air de fonctionner
function html_link($url, $link_name, $link_decoration = null) {
    global $CFG;
    return '<a target = "_blank" href="' . $CFG->webpath . '/' . $url . '">' . $link_decoration . format_string($link_name) . '</a>';
}

// Fonction supprimant le cours du site Moodle de la liste de cours.
function remove_site_course($courses) {
    $site = get_site();
    if (array_key_exists($site->id,$courses))
    {
        unset($courses[$site->id]);
    }
    return $courses;
}

// copie de la function course/lib/print_overview avec quelques modifications
// de mise en forme
function RUNN_print_overview($courses, $detail=false) {
    global $CFG, $USER, $DB;
    $htmlarray = array();

     // KFN : Mis en commentaire car parfois long à charger et dépasse le timeout du portlet
    // Les details des cours ne sont charges qu'au besoin
    // INFO : Mis en commentaire car peut necessiter trop de temps a s'executer
    //        pour les personnes ayant beaucoup de cours
    
//     if ($detail && $modules = $DB->get_records('modules')) {
//         foreach ($modules as $mod) {
//             if (file_exists(dirname(dirname(__FILE__)).'/mod/'.$mod->name.'/lib.php')) {
//                 include_once(dirname(dirname(__FILE__)).'/mod/'.$mod->name.'/lib.php');
//                 $fname = $mod->name.'_print_overview';
//                 if (function_exists($fname)) {
//                     $fname($courses,$htmlarray);
//                 }
//             }
//         }
//     }
    

    foreach ($courses as $course) {
        $course_url = "course/view.php?id=$course->id";
        $course_name = $course->fullname;
        print('<dt class="course">'
// Devenu inutile avec la nouvelle version qui s'appuie sur le block course_overview_esco ? CD - 06/12/2016
//        	.'	<input type="hidden" value="'.$course->roles_esco.'">'
//        	.'	<input type="hidden" value="'.$course->timecreated.'">'
        	. html_link($course_url, $course_name)
        	. '</dt>');
        // Affichage du detail ?
        if ($detail && array_key_exists($course->id,$htmlarray)) {
            foreach ($htmlarray[$course->id] as $modname => $html) {
                echo '<dd class="course">'.$html.'</dd>';
            }
        }
    }
}

// Fonction permettant de mettre a jour la date de derniere acces de chaque cours
// present dans la liste
function update_last_access($courses) {
    global $USER;

    foreach ($courses as $c) {
        if (isset($USER->lastcourseaccess[$c->id])) {
                $courses[$c->id]->lastaccess = $USER->lastcourseaccess[$c->id];
            } else {
                $courses[$c->id]->lastaccess = 0;
            }
    }
    return $courses;
}
?>

<!DOCTYPE html> 
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Moodle</title>
        <link rel="stylesheet" href="./moodle-ent/css/jquery-ui.css" />
        <link rel="stylesheet" href="./moodle-ent/css/moodle-ent.css" />
	<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/icon?family=Material+Icons">
        <script src="./moodle-ent/js/jquery.min.1.9.1.js" type="text/javascript"></script>
	<script src="./moodle-ent/js/jqueryui.min1102.js" type="text/javascript"></script>
        
        <script type="text/javascript">
			var themeUrl = <?= '"'.$CFG->wwwroot.'/theme/image.php/'.$CFG->theme.'/core/'.$CFG->themerev.'/t'.'"'; ?>;
        </script>
        <script src="./moodle-ent/js/moodle-ent.js" type="text/javascript"></script>
        <script src="./moodle-ent/js/domain.js" type="text/javascript"></script>
	<!--         <script src="./moodle-ent/js/js-control-overviews.js" type="text/javascript"></script> -->
    </head>
    <body>
        <div class="moodle-courses">        
            <div class="portlet-section">
                <img title="Chargement" alt="Chargement" id="loading" src="./moodle-ent/img/ajax-loading.gif" />
                <div id="tabs" class="portlet-section-body">
			<?php				
				$moodle_link_name = 'Tableau de bord Moodle';
				$link_decoration = '<i class="material-icons glyphicon">view_list</i>';
			?>
			<div class="moodledropdown">
				<button onclick="myFunction()" class="dropbtnCours"><i class="material-icons glyphicon">school</i>Mes espaces de cours Moodle<span class="caret"></span></button>
				<div id="myDropdownCours" class="dropdown-content">
				    	<a id="t-1" onclick="javascript:evalLink(1)"><span><i class="material-icons glyphicon">school</i>Mes espaces de cours Moodle</span></a>
				      	<a id="t-2" onclick="javascript:evalLink(2)"><span><i class="material-icons glyphicon">vpn_key</i>Cours en auto-inscription avec cl&eacute;</span></a>
				      	<a id="t-3" onclick="javascript:evalLink(3)"><span><i class="material-icons glyphicon">lock_open</i>Cours en auto-inscription sans cl&eacute;</span></a>
				      	<a id="t-4" onclick="javascript:evalLink(4)"><span><i class="material-icons glyphicon">sentiment_very_satisfied</i>Cours en acc&egrave;s libre</span></a>

					<?php
						//print('<span>' . html_link('',$moodle_link_name) . '</span>');
						print('<span>' . html_link('',$moodle_link_name,$link_decoration) . '</span>');
					?>
				</div>
			</div>

			<div class="menuCoursMoodle">

			    	<p class="menuWPPMoodle menuPmoodle1 activeWPP"><a id="tx-1" onclick="javascript:evalLink(1)"><span><i class="material-icons glyphicon">school</i>Mes espaces de cours Moodle</span></a></p>
			      	<p class="menuWPPMoodle menuPmoodle2"><a id="tx-2" onclick="javascript:evalLink(2)"><span><i class="material-icons glyphicon">vpn_key</i>Cours en auto-inscription avec cl&eacute;</span></a></p>
			      	<p class="menuWPPMoodle menuPmoodle3"><a id="tx-3" onclick="javascript:evalLink(3)"><span><i class="material-icons glyphicon">lock_open</i>Cours en auto-inscription sans cl&eacute;</span></a></p>
			      	<p class="menuWPPMoodle menuPmoodle4"><a id="tx-4" onclick="javascript:evalLink(4)"><span><i class="material-icons glyphicon">sentiment_very_satisfied</i>Cours en acc&egrave;s libre</span></a></p>
				<!--  <li><a href="#tabs-5"><img alt="Logo recent" width="24" src="./moodle-ent/img/DocumentNew.png"/><span>Activit&eacute;s r&eacute;centes</span></a></li>  -->
				<?php
					//print('<p class="menuWPPMoodle"><img alt="Logo recent" width="24" src="./moodle-ent/img/moodle-24.png"/>' . html_link('',$moodle_link_name) . '</p>'); 
					print('<p class="menuWPPMoodle">' . html_link('',$moodle_link_name,$link_decoration) . '</p>'); 
				?>

			</div>

			<div id="main-container">
				<div id="tabs-1">
					<h2 class="main-container-title">MES ESPACES DE COURS MOODLE</h2>
				   	<?php include_once '../blocks/course_overview_esco/sort_and_filter.php'; ?>
					<dl>
					<?php
						$systemcontext = get_context_instance(CONTEXT_SYSTEM);
						$PAGE->set_url('/local/moodle-ent.php');
						$PAGE->set_context($systemcontext);
						$uid = $_GET['uid'];
						$anuser = $DB->get_record("user", array("username"=>$uid));
							// $anuser = true;
						if ($anuser) {
							$mymoodlestr = get_string('mymoodle','my');
								// The main overview
							$courses_limit = 21;
							if (isset($CFG->mycoursesperpage)) {
								$courses_limit = $CFG->mycoursesperpage;
							}
							$morecourses = false;
								// Test de performance de la requete suivante 
								//     require_once($CFG->libdir. '/coursecatlib.php');
								//     $coursecat = coursecat::get(0);
								//     $option['recursive'] = 1;
								//     $courses = $coursecat->get_courses($option);
								//     error_log("\n COURSES : ".print_r($courses,true));
							$courses = enrol_get_users_courses( $anuser->id, true, 'id, fullname, timecreated', 'fullname ASC' );
								//'timecreated' necessaire pour pouvoir trier les cours par date
								// error_log("\n contains of loaded courses : ".print_r($courses, true), 3, "/tmp/myerrorfile2.log");
								// 	$courses = enrol_get_my_courses('timecreated');
		 					if(!empty($courses)) {    	
	    							$courses = add_roles($anuser->id, $courses);
								// Recuperation du bloc "course_overview_esco"
		    						$instance = $DB->get_record('block_instances', array('blockname' => 'course_overview_esco'));
		    						$block_course_overview_esco = block_instance('course_overview_esco', $instance);
		    						$renderer = $block_course_overview_esco->page->get_renderer('block_course_overview_esco');
								//$overviews = "Test mc" - retire le 05/12/2016 par CD car trop long à s'exécuter et inutilisé dans la vue fournie... 
								//$overviews = block_course_overview_esco_get_overviews($courses);
								$overviews = null;
								// Modif RECIA-CD - 20160201 => pour affichage des liens dans nouvel onglet avec la WebProxyPortlet
								//$html = $renderer->course_overview_esco($courses, $overviews);
								$html = $renderer->course_overview_esco($courses, $overviews, true);
								// 	$html = $wwprenderer->get_WPP_content($courses, overviews);
		   						// error_log("\n contains of html : ".print_r($html,true),3, "/tmp/myerrorfile2.log");
	    						} else {
								$html = "<p class='no_course'>".get_string('noCourse', 'block_course_overview_esco')."</p>";
							}
								// Affichage des cours
							print($html);
	    						print('</dl></div>');
							print('<div id="tabs-2"><h2 class="main-container-title">COURS EN AUTO-INSCRIPTION AVEC CL&Eacute;</h2><dl>');
								// Cours en auto-inscription avec cle
								//	error_log("\n DEBUT APPEL FONCTION GET_SELF_ENROL_WITH_PASSWORD
								//	(2eme page : Cours en auto-inscription avec cle)", 3, "/tmp/myBenchmark.log");
								//        $hDebut = microtime(true);
							$self_courses = get_self_enrol_course_with_password();
							$self_courses = remove_site_course($self_courses);
							$self_courses = update_last_access($self_courses);
	    							// Affichage des cours
							display_courses($self_courses, false);
	    						print('</dl></div>');
	    						print('<div id="tabs-3"><h2 class="main-container-title">COURS EN AUTO-INSCRIPTION SANS CL&Eacute;</h2><dl>');
								// Cours en auto-inscription sans cle
								//	error_log("\n DEBUT APPEL FONCTION GET_SELF_ENROL_WITHOUT_PASSWORD
								//	(3eme page : Cours en auto-inscription sans cle)", 3, "/tmp/myBenchmark.log");
								//        $hDebut = microtime(true);
							$self_courses = get_self_enrol_course_without_password(); 
							$self_courses = remove_site_course($self_courses);
							$self_courses = update_last_access($self_courses);
								// Affichage des cours
							display_courses($self_courses, false);
							print('</dl></div>');
							print('<div id="tabs-4"><h2 class="main-container-title">COURS EN ACC&Egrave;S LIBRE</h2><dl>');
								// Cours en acces libre
							$guest_courses = get_guest_enrol_course(); 
							$guest_courses = remove_site_course($guest_courses);
	    						$guest_courses = update_last_access($guest_courses);
	    							// Affichage des cours
							display_courses($guest_courses, false);
								/* Retire par CD car inutile - 20151126:
								    print('</dl></div>');
								    print('<div id="tabs-5"><dl>');

								    // Recuperation des cours (avec dernier acces) dans lequel l'utilisateur a un role
								    $enrolled_courses = get_enrolled_courses($uid);
								    
								    // Recuperation des modifications par cours
								    $recent_activities = get_courses_mod_logs($enrolled_courses);
								    display_recent_activities($recent_activities);
								*/

						} else {
						    		// Utilisateur inconnu , ne pas donner l'info, juste pas de cours
								// FIXME : cette fonction genere une fatal error tjrs car elle n'existe plus au sein de moodle 
								// TODO : A remplacer 
							print_simple_box(get_string('nocourses','my'),'generalbox',NULL);
								/* Modifie par CD car inutile - 20151126: 
								//	print('<div id="tabs-2"></div><div id="tabs-3"></div><div id="tabs-4"></div><div id="tabs-5"></div>'); */
							print('<div id="tabs-2"></div><div id="tabs-3"></div><div id="tabs-4"></div>'); 
						}
						?>
				    		</dl>
			    			</div>
		    			</div>
				</div>
			</div>
		</div>
		<script>
			$( "#tabs-1" ).css("display" , "block");
			$( "#tabs-2" ).css("display" , "none");
			$( "#tabs-3" ).css("display" , "none");
			$( "#tabs-4" ).css("display" , "none");
			//$( ".menuPmoodle1" ).css("background-color" , "#e1e1e1");
			//$( ".menuPmoodle1 a" ).css("color" , "#303030");
			//$( ".menuPmoodle1 a" ).css("font-weight" , "bold");
			//$("ul").removeClass("ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all");
			//$(".moodle-courses ul .teachers .folded").removeClass("ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all");
		</script>
	</body>
</html>

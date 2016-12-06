/*
 * Avec IE , à chaque fois que l'on clique sur "Afficher le résumé" ou "Afficher les enseignants",
 * l'évènement $(window).resize est appelé, ce qui peut provoquer une boucle infinie selon les cas.
 * Pour éviter ce comportement, on vérifie si la largeur du navigateur a été modifiée.  
 */
var lastDocumentWidth;

$(function() {
	$("#loading").hide();
	$("#tabs").show(); 
	$("#tabs").tabs();
	
	// S'il y a au moins un cours
	if($("div#tabs-1 div.coursebox").length > 0) {
		// On affiche les flèches devant "Afficher les enseignants"
		$("#tabs-1 .block-hider-show").css("background-image", "url('" + themeUrl + "/collapsed')");
		$("#tabs-1 .block-hider-hide").css("background-image", "url('" + themeUrl + "/expanded')");
	} else {
		$("div#tabs-1").html($("div#tabs-1 dl").html()); //On efface le tri et le filtre 
	}
    
    configDisplay(false);
    lastDocumentWidth = $(document).width();
    
    $(window).resize(function() {
    	if(window.navigator.appName.indexOf("Internet Explorer") == -1 || $(document).width() != lastDocumentWidth) {
    		lastDocumentWidth = $(document).width();    
    		configDisplay(true);
    	}
    });
    
    // Lorsqu'on clique sur "Il y a de nouveaux messages de forum"
    $("img.iconlarge").parent().attr("href", "#");
    $("img.iconlarge").parent().removeAttr("target");
    $("img.iconlarge").click(function() {
    	var div = $(this).parent().parent().next();
    	if(div.is(":visible")) {
    		div.hide();
    	} else {
    		div.find("a").attr("target", "_blank");
    		div.show();
    	}
    });
});

function configDisplay(isResized) {
	configMenu(isResized);
	configSummary();
}


/**
 * Affiche le menu hamburger selon la taille du module
 */
function configMenu(isResized) {
	var menuIcon 	= $("#hamburger");
	var menu 	= $("#tab-container");
	var menuOption 	= $("#tab-container li");
	var courses 	= $("#main-container");
	menuOption.unbind("click");
	menuIcon.hide();
	// Si l'affichage est large 
	if($("#tabs").width() >= 954) {
		
		// On masque le menu hamburger
		menuIcon.hide();
		menu.css("opacity", "1.0");
		menu.show();
		courses.css("margin", "0 0 0 230px");
		courses.css("padding", "10px");
		menu.css("border", "0px solid #cccccc");
		menu.css("width", "300px");
		$("h2.main-container-title").css("display", "block");
		//$("#tab-container").css("position", "fixed");
		$("#tab-container").css("margin-top", "10px");
		$(".moodledropdown").css("display", "none");
		$(".menuCoursMoodle").css("display", "block");
	
	// Si l'affichage n'est pas très large
	} else {

		$("#tab-container a").click(function(event){
		    event.preventDefault();
		});
		//menuIcon.show();
		//menu.css("opacity", "0.0");
		//menu.hide();
		menu.css("opacity", "1");
		courses.css("margin", "0px");
		courses.css("padding", "10px");
		$("#tab-container").css("margin-bottom", "20px");
		menu.css("border", "1px solid #cccccc");
		menu.css("width", "auto");
		menu.css("display", "block");
		$("#main-container #tabs-1").css("padding-top", "auto");
		$("h2.main-container-title").css("display", "none");
		$("#tab-container").css("display", "none");
		$("#tab-container").css("margin-right", "12px");
		$("#tab-container").css("margin-top", "-12px");
		$(".moodledropdown").css("display", "block");
		$(".menuCoursMoodle").css("display", "none");
		
		// Lorsqu'on clique sur le menu
		menuIcon.unbind("click");
		menuIcon.click(function() {
			//menu.css("position", "fixed");
			menu.css("top", menuIcon.offset().top - 4);
			//displayMenu(menu.is(":hidden"));
			courses.animate({top: '250px'});
		});
		
		// Lorsqu'on clique sur un élément du menu
		menuOption.click(function() {
			//displayMenu(false);
		});
	}

	var sortSelect = $("select[name='sortOrder']");
	var filterSelect = sortSelect.next().next();
	
	sortSelect.addClass("form-control");
	$("select[name='rolesFilter']").addClass("form-control");
	
	// Si on est en version mobile
	if($(".ui-mobile").length > 0) {
// On ne voit pas l'intérêt d'augmenter la taille !!! - CD 20151127
//		$("div.ui-select:eq(0)").next().css("font-size", "18px"); //On augmente la taille de "Filtrer par profil :"
		sortSelect.css("padding-top", "5px");
		sortSelect.css("padding-bottom", "5px");
		filterSelect.css("padding-top", "5px");
		filterSelect.css("padding-bottom", "5px");
		filterSelect.css("margin", "10px");
	} else {
		// On affiche correctement le filtre par rôle
		sortSelect.css("display", "inline");
		sortSelect.css("margin-bottom", "0px");
		var topSortSelect = sortSelect.position().top;
		var topFilterSpan = sortSelect.next().position().top;
		var topFilterSelect = filterSelect.position().top;
		if(Math.abs(topFilterSpan - topFilterSelect) > 5) {
			sortSelect.show();
		}
		if(Math.abs(topSortSelect - topFilterSelect) > 5) {
			sortSelect.css("margin-bottom", "10px");					
		}
	}
	
/*	if($("#tabs").width() >= 944) {
		sortSelect.css("margin-top", "0px");
	}else{
		sortSelect.css("width", "100%");
		$("select[name='rolesFilter']").css("width", "100%");
	}*/

	// Lorsqu'on clique sur "Afficher les enseignants"
	if(!isResized) addTeachersEvent();
}

/* ACTIVE DROPDOWN */

function myFunction() {
    document.getElementById("myDropdownCours").classList.toggle("show");
}

// Close the dropdown menu if the user clicks outside of it
window.onclick = function(event) {
  if (!event.target.matches('.dropbtnCours')) {
    var dropdowns = document.getElementsByClassName("dropdown-content");
    var i;
    for (i = 0; i < dropdowns.length; i++) {
      var openDropdown = dropdowns[i];
      if (openDropdown.classList.contains('show')) {
        openDropdown.classList.remove('show');
      }
    }
  }
}

/* MODIFICATION MENU ET SUPPRESSION DE LA FONCTION TABS() */

function evalLink(numTab)
{
	for (i = 1; i < 5; i++) {
	  if (i == numTab) {
	    $( "#tabs-"+i ).css("display" , "block");
	    $( ".menuPmoodle"+i ).addClass("activeWPP");
	  } else {
	    $( "#tabs-"+i ).css("display" , "none");
	    $( ".menuPmoodle"+i ).removeClass("activeWPP");
	  }
	}
	  switch (numTab) {
	    case 1:
		$(".dropbtnCours").html('<i class="material-icons glyphicon">school</i>Mes espaces de cours Moodle<span class="caret"></span>');
		break;
	    case 2:
		$(".dropbtnCours").html('<i class="material-icons glyphicon">vpn_key</i>Cours en auto-inscription avec cl&eacute;<span class="caret"></span>');
		break;
	    case 3:
		$(".dropbtnCours").html('<i class="material-icons glyphicon">lock_open</i>Cours en auto-inscription sans cl&eacute;<span class="caret"></span>');
		break;
	    case 4:
		$(".dropbtnCours").html('<i class="material-icons glyphicon">sentiment_very_satisfied</i>Cours en acc&egrave;s libre<span class="caret"></span>');
		break;
	    default:
		$(".dropbtnCours").html('<i class="material-icons glyphicon">school</i>Mes espaces de cours Moodle<span class="caret"></span>');
	    }
}
	  
/**
 * visible = true : ouvre le menu
 * visible = false : ferme le menu  
 */
function displayMenu(visible) {
	if(visible) {
		var tagToDisplay = $("#tab-container");
		var tagToHide = $("#main-container");
		var marginLeft = "310px";
		var marginTop = "0px";
		tagToDisplay.animate({width: '97%'});
	} else {
		var tagToDisplay = $("#main-container");
		var tagToHide = $("#tab-container");
		var marginLeft = "0px";
		var marginTop = "0px";
		tagToHide.hide(5);
	}

	tagToDisplay.show(5);
	tagToHide.animate({opacity: '1.0'}, function() {
		tagToDisplay.animate({opacity: '1.0'});
		//tagToHide.hide();
	    $("#main-container").animate({"marginTop": marginTop}, {
		    duration: 10,
            complete: function () {
            	tagToDisplay.animate({opacity: '1.0'});
        	}
		});
	});
}

/**
 * Affiche "Afficher le résumé" selon la taille du module
 */
function configSummary() {
	var summary = $("#tabs-1 div.summary_reply.fold_reply");
// Retitiré pour que les résumés ne s'affichent pas dans le portail ENT directement
//      if($(window).width() >= 760) {
 //       if($(window).width() >= 5760) {
//		summary.hide();
//		summary.next().show();
//	} else {
// Adaptation MCisse => 2016/02/16 
// On affiche le bloc resume quelque soit la taille de la fenetre ! 

		summary.show();
		summary.next().hide();
//	}
	addSummaryEvent();
}

/**
 * Lorsqu'on clique sur "Afficher le résumé"
 */
function addSummaryEvent() {
	addEvent($("#tabs-1 div.summary_reply.fold_reply"));
}

/**
 * Lorsqu'on clique sur "Afficher les enseignants"
 */
function addTeachersEvent() {
	addEvent($("#tabs-1 .teachers_reply.fold_reply"));
}

function addEvent(tag) {
	tag.unbind("click");
	
	tag.next().removeClass("ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all");	

	tag.click(function(e) {
		var tagShow = $(e.currentTarget).find(".block-hider-show");
		var tagHide = $(e.currentTarget).find(".block-hider-hide");
		var tagFolded = $(e.currentTarget).next();

		if(tagShow.is(":visible")) {
			tagShow.hide();
			tagHide.show();
			tagFolded.show();			
		} else {
			tagShow.show();
			tagHide.hide();
			tagFolded.hide();
		}
		
	});
}



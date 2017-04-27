<?php

use DIQA\SemanticTranslation\SemanticTitleTranslator;
/**
 * Semantic Translation for [http://www.mediawiki.org/wiki/Extension:SemanticForms Semantic Forms].
 *
 * @defgroup DIQAsemanticTranslation
 *
 * @author Kai Kühn
 *
 * @version 0.1
 */

/**
 * The main file of the DIQA Semantic Translation extension
 *
 * @file
 * @ingroup DIQA
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is part of a MediaWiki extension, it is not a valid entry point.' );
}

define( 'DIQA_SEMANTICTRANSLATION_VERSION', '0.1' );

global $wgVersion;
global $wgExtensionCredits;
global $wgExtensionMessagesFiles;
global $wgHooks;
global $wgResourceModules;

// register extension
$wgExtensionCredits[ 'odb' ][] = array(
	'path' => __FILE__,
	'name' => 'Semantic Translation',
	'author' => array( 'DIQA Projektmanagement GmbH' ),
	'license-name' => 'GPL-2.0+',
	'url' => 'http://www.diqa-pm.com',
	'descriptionmsg' => 'diqa-semanticTranslation-desc',
	'version' => DIQA_SEMANTICTRANSLATION_VERSION,
);

$dir = dirname( __FILE__ );

$wgExtensionMessagesFiles['DIQAsemanticTranslation'] = $dir . '/DIQAsemanticTranslation.i18n.php';
$wgHooks['ParserFirstCallInit'][] = 'wfDIQAsemanticTranslationSetup';

$wgHooks['ParserFirstCallInit'][] = 'wfDIQASemanticTitleTranslationRegisterParserHooks';

global $wgSTFieldsToTranslate;
$wgSTFieldsToTranslate = [];

function wfDIQASemanticTitleTranslationRegisterParserHooks(Parser $parser)
{
	$currentTitle = RequestContext::getMain()->getTitle();

	Hooks::register('PageForms::CreateFormField', function (&$form_field, &$cur_value_in_template, $submitted) {
		SemanticTitleTranslator::translateTitle($form_field, $cur_value_in_template, $submitted);
	});

	Hooks::register('sfRenderFormField', function (&$form_field, &$cur_value) {
		SemanticTitleTranslator::renderInputField($form_field, $cur_value);
	});

	return true;
}
/**
 * Initializations for DIQAsemanticTranslation
 */
function wfDIQAsemanticTranslationSetup() {
	
	return true;
}

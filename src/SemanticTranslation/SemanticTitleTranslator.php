<?php

namespace DIQA\SemanticTranslation;

use SMW\StoreFactory;

/**
 * SemanticTitleTranslator
 *
 * @author Kai
 *
 */
class SemanticTitleTranslator {


	/**
	 * Translates the PageTitle with the SemanticTitle and vice-versa.
	 *
	 * Assumptions:
	 *
	 * 	1. Mapping from PageTitle to SemanticTitle is one-to-one (possibly filtered by category).
	 *  2. SemanticTitle attribute is the same for all namespaces.
	 *
	 * @param SFFormField $form_field
	 * @param string $cur_value_in_template
	 * @param boolean $submitted
	 */
	public static function translateTitle(&$form_field, &$cur_value_in_template, $submitted) {

		$semanticTitleText = self::getTitleProperty();

		if ($cur_value_in_template == '') {
			return;
		}

		$fullyQualified = false;

		// parses the field name to check if this field must be translated
		global $wgSTFieldsToTranslate;
		preg_match_all('/\[([^]]*)\]/', $form_field->getInputName(), $matches);
		if (!isset($matches[1])) {
			return;
		}

		if (in_array($form_field->getInputName(), $wgSTFieldsToTranslate)) {

			// fully qualified fieldname (ie. with template)
			$fieldName = isset($matches[1][1]) ? $matches[1][1] : $matches[1][0];
			$fullyQualified = true;
		} else {

			// only field name is specified
			if (!in_array($matches[1][0], $wgSTFieldsToTranslate)) {

				if (!isset($matches[1][1]) || !in_array($matches[1][1], $wgSTFieldsToTranslate)) {
					return;
				}
				$fieldName = $matches[1][1];
			} else {
				$fieldName = $matches[1][0];
			}
		}

		// translate the field name from title -> page ID or page -> title depending if it is a form-save or a form-load
		$store = StoreFactory::getStore ();

		if ($submitted) {

			$fieldArgs = $form_field->getFieldArgs();
			$delimiter = isset($fieldArgs['delimiter']) ? $fieldArgs['delimiter'] : ',';
			$labels = explode($delimiter, $cur_value_in_template);

			$translatedInstances = array();
			foreach($labels as $label) {
				$label = trim($label);
				if ($label == '') {
					continue;
				}
				$subjects = $store->getPropertySubjects ( \SMWDIProperty::newFromUserLabel ( $semanticTitleText ), new \SMWDIBlob ( $label ) );
				if (count($subjects) === 1) {
					// mapping is unique
					$subject = reset($subjects);
				} else {
					$subject = static::getTitleOfCategory($subjects, static::mapFieldNameToCategories($fieldName), $fullyQualified);

					if (is_null($subject)) {

						// no matching category found, do not translate at all.
						$translatedInstances[] = $label;
						continue;

					}
				}
				$translatedInstances[] = $subject->getTitle ()->getPrefixedText ();
			}
			$cur_value_in_template = implode("$delimiter ", $translatedInstances);

		} else {
			$fieldArgs = $form_field->getFieldArgs();
			$delimiter = isset($fieldArgs['delimiter']) ? $fieldArgs['delimiter'] : ',';
			$instances = explode($delimiter, $cur_value_in_template);

			$translatedLabels = array();
			foreach($instances as $instance) {
				$instance = trim($instance);
				$title = \Title::newFromText($instance);
				if (is_null($title)) {
					$translatedLabels[] = $instance;
					continue;
				}
				$subject = \SMWDIWikiPage::newFromTitle($title);
				$values = $store->getPropertyValues($subject, \SMWDIProperty::newFromUserLabel ( $semanticTitleText ));
				$first = reset ( $values );
				if ($first === false) {
					$translatedLabels[] = $instance;
					continue;
				}
				$translatedLabels[] = $first->getString();
			}
			$cur_value_in_template = implode("$delimiter ", $translatedLabels);
		}
	}

	/**
	 * Maps fieldnames to categories on which they are used.
	 * Usually, there is a 1:1 mapping
	 *
	 * @param string $fieldName
	 * @return string []
	 */
	private static function mapFieldNameToCategories($fieldName) {
		
		global $wgDIQASemanticTranslationMappings;
		if (!isset($wgDIQASemanticTranslationMappings) 
			|| !array_key_exists($fieldName, $wgDIQASemanticTranslationMappings)) {
			$categories = [ $fieldName ];
		} else {
			$mapping = $wgDIQASemanticTranslationMappings[$fieldName];
			$categories = is_array($mapping) ? $mapping : [ $mapping ];
		}
		
		global $wgContLang;
		$categoryNsText = $wgContLang->getNsText( NS_CATEGORY );
		return array_map(function($e) {
			return "$categoryNsText:$e";
		}, $categories);
	}

	/**
	 * Returns the (first) subject which is member of at least one of categories.
	 *
	 * @param \SMWDIWikiPage [] $subjects
	 * @param string [] $categories
	 * @param bool $fullyQualified
	 * @return \SMWDIWikiPage|NULL
	 */
	private static function getTitleOfCategory($subjects, $categories, $fullyQualified) {

		if ($fullyQualified) {
			return count($subjects) > 0 ? reset($subjects) : NULL;
		}

		// if not fully qualified check the category of subject
		foreach($subjects as $subject) {
			$parentCategories = array_keys($subject->getTitle()->getParentCategories());
			$intersect = array_intersect($parentCategories, $categories);

			if (count($intersect) > 0) {
				return $subject;
			}
		}

		return NULL;
	}

	/**
	 * Translates page ID into title when rendering the form field
	 * 
	 * @param \SFFormField $input_field
	 * @param string $cur_value
	 */
	public static function renderInputField(&$input_field, &$cur_value) {
		
		global $wgSTFieldsToTranslate;
		
		$semanticTitleText = self::getTitleProperty();

		if ($cur_value == '') {
			return;
		}

		preg_match_all('/\[([^]]*)\]/', $input_field->getInputName(), $matches);
		if (!isset($matches[1])) {
			return;
		}

		if (!in_array($matches[1][0], $wgSTFieldsToTranslate)) {
			return;
		}

		$store = StoreFactory::getStore ();

		$subject = \SMWDIWikiPage::newFromTitle(\Title::newFromText($cur_value));
		$values = $store->getPropertyValues($subject, \SMWDIProperty::newFromUserLabel ( $semanticTitleText ));
		$first = reset ( $values );
		if ($first !== false) {
			$cur_value = $first->getString();
		}
	}
	
	private static function getTitleProperty() {
		global $wgSTProperty;
		if (!isset($wgSTProperty)) {
			$semanticTitleText = wfMessage('diqa-stt-title-default')->text();
		} else {
			$semanticTitleText = $wgSTProperty;
		}
		return $semanticTitleText;
	}

}
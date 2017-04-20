# diqa-semantic-translation
Converts title-attributes into wiki titles and vice-versa

#
# Configuration
#

1. $wgSTFieldsToTranslate

	Array of field names to translate, e.g. 
		
		[ 'Bau', 'Ensemble' ]
		   
		meaning all fields with these names are translated in 
		every form where they appear.
		
	It is also possible to fully qualify the field names, e.g.
	
		[ 'Objekt[Bau]', 'Ensemble' ]
	
		meaning that the field 'Bau' is only translated in the form 'Objekt'.
		
2. $wgSTProperty

	Name of Title-Property 
	
	(if missing a default is used depending on the content language of the wiki)


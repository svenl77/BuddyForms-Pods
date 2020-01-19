<?php

/*
 * Add PODS form elementrs in the form elements select box
 */
add_filter( 'buddyforms_add_form_element_select_option', 'buddyforms_pods_elements_to_select', 1, 2 );
function buddyforms_pods_elements_to_select( $elements_select_options ) {
	global $post;

	if ( $post->post_type != 'buddyforms' ) {
		return $elements_select_options;
	}

	if( ! defined('PODS_VERSION')){
		return $elements_select_options;
	}

	$elements_select_options['pods']['label']                = 'PODS';
	$elements_select_options['pods']['class']                = 'bf_show_if_f_type_post';
	$elements_select_options['pods']['fields']['pods-field'] = array(
		'label' => __( 'PODS Field', 'buddyforms' ),
	);

	$elements_select_options['pods']['fields']['pods-group'] = array(
		'label' => __( 'PODS Fields', 'buddyforms' ),
	);

	return $elements_select_options;
}

/*
 * Create the new PODS Form Builder Form Elements
 *
 */
add_filter( 'buddyforms_form_element_add_field', 'buddyforms_pods_form_builder_form_elements', 1, 5 );
function buddyforms_pods_form_builder_form_elements( $form_fields, $form_slug, $field_type, $field_id ) {
	global $field_position, $buddyforms;

	if( ! defined('PODS_VERSION')){
		return $form_fields;
	}

	$pods            = pods_api()->load_pods( array( 'fields' => false ) );
	$pod_form_fields = array();
	$pods_list       = array();
	foreach ( $pods as $pod_key => $pod ) {
		$pods_list[ $pod['name'] ] = $pod['label'];
		foreach ( $pod['fields'] as $pod_fields_key => $field ) {
			$pod_form_fields[ $pod['name'] ][ $field['name'] ] = $field['label'];
		}
	}

	switch ( $field_type ) {
		case 'pods-field':

			unset( $form_fields );

			$pods_group = 'false';
			if ( isset( $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['pods_group'] ) ) {
				$pods_group = $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['pods_group'];
			}

			$form_fields['general']['pods_group'] = new Element_Select( '', "buddyforms_options[form_fields][" . $field_id . "][pods_group]", $pods_list, array(
				'value'         => $pods_group,
				'class'         => 'bf_pods_field_group_select',
				'data-field_id' => $field_id
			) );

			$pods_field = 'not-set';
			if ( isset( $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['pods_field'] ) ) {
				$pods_field = $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['pods_field'];
			}
			$field_select                         = $pod_form_fields[$pods_group];
			$form_fields['general']['pods_field'] = new Element_Select( '', "buddyforms_options[form_fields][" . $field_id . "][pods_field]", $field_select, array(
				'value' => $pods_field,
				'class' => 'bf_pods_fields_select bf_pods_' . $field_id
			) );

			$name = 'PODS-Field';
			if ( $pods_field != 'not-set' ) {
				$name = 'PODS Field: ' . $pods_field;
			}
			$form_fields['general']['name'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][name]", $name );

			$form_fields['general']['slug']  = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][slug]", $pods_field );
			$form_fields['general']['type']  = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][type]", $field_type );
			$form_fields['general']['order'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][order]", $field_position, array( 'id' => 'buddyforms/' . $form_slug . '/form_fields/' . $field_id . '/order' ) );
			break;
		case 'pods-group':

			unset( $form_fields );


			$pods_group = 'not-set';
			if ( isset( $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['pods_group'] ) ) {
				$pods_group = $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['pods_group'];
			}
			$form_fields['general']['pods_group'] = new Element_Select( '', "buddyforms_options[form_fields][" . $field_id . "][pods_group]", $pods_list, array( 'value' => $pods_group ) );

			$name = 'PODS-Group';
			if ( $pods_group != 'not-set' ) {
				$name = ' PODS Group: ' . $pods_group;
			}
			$form_fields['general']['name'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][name]", $name );

			$form_fields['general']['slug']  = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][slug]", $pods_group );
			$form_fields['general']['type']  = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][type]", $field_type );
			$form_fields['general']['order'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][order]", $field_position, array( 'id' => 'buddyforms/' . $form_slug . '/form_fields/' . $field_id . '/order' ) );
			break;

	}

	return $form_fields;
}

/*
 * Display the new PODS Fields in the frontend form
 *
 */
add_filter( 'buddyforms_create_edit_form_display_element', 'buddyforms_pods_frontend_form_elements', 1, 2 );
function buddyforms_pods_frontend_form_elements( $form, $form_args ) {
	global $buddyforms, $nonce;

	extract( $form_args );

	$post_type = $buddyforms[ $form_slug ]['post_type'];

	if ( ! $post_type ) {
		return $form;
	}

	if ( ! isset( $customfield['type'] ) ) {
		return $form;
	}

	if( ! defined('PODS_VERSION')){
		return $form_fields;
	}

	$pods = pods_api()->load_pods( array( 'fields' => false ) );

	$pod_form_fields = array();
	$pods_list       = array();
	foreach ( $pods as $pod_key => $pod ) {
		$pods_list[ $pod['id'] ] = $pod['name'];
		foreach ( $pod['fields'] as $pod_fields_key => $field ) {
			$pod_form_fields[ $pod['name'] ][ $pod_fields_key ] = $field['name'];
		}
	}

	$script_out = sprintf( '<script type="text/javascript"> if ( "undefined" === typeof ajaxurl ) { ajaxurl = "%s"; } </script>', pods_slash( admin_url( 'admin-ajax.php' ) ) );

	$form->addElement( new Element_HTML( $script_out ) );

	switch ( $customfield['type'] ) {
		case 'pods-field':

			$mypod = pods( $customfield['pods_group'], $post_id );
			if ( ! count( $mypod->pod_data['fields'] ) > 0 ) {
				break;
			}

			$params = array( 'fields_only' => true, 'fields' => $customfield['pods_field'] );

			$form->addElement( new Element_HTML( $mypod->form( $params ) ) );

			break;
		case 'pods-group':

			$mypod = pods( $customfield['pods_group'], $post_id );
			if ( ! count( $mypod->pod_data['fields'] ) > 0 ) {
				break;
			}

			$params = array( 'fields_only' => true, 'fields' => $pod_form_fields[ $customfield['pods_group'] ] );

			$form->addElement( new Element_HTML( $mypod->form( $params ) ) );
	}

	return $form;
}

/*
 * Save PODS Fields
 *
 */
add_action( 'buddyforms_update_post_meta', 'buddyforms_pods_update_post_meta', 10, 2 );
function buddyforms_pods_update_post_meta( $customfield, $post_id ) {
	if ( $customfield['type'] == 'pods-group' ) {

		if( ! defined('PODS_VERSION')){
			return;
		}

		$pods = pods_api()->load_pods( array( 'fields' => false ) );

		$pod_form_fields = array();
		$pods_list       = array();
		foreach ( $pods as $pod_key => $pod ) {
			$pods_list[ $pod['id'] ] = $pod['name'];
			foreach ( $pod['fields'] as $pod_fields_key => $field ) {
				$pod_form_fields[ $pod['name'] ][ $pod_fields_key ] = $field['name'];
			}
		}

		$pod = pods( $customfield['pods_group'], $post_id );
		foreach ( $pod_form_fields[ $customfield['pods_group'] ] as $kk => $field_name ) {
			$data[ $field_name ] = $_POST[ $field_name ];
		}

		$pod->save( $data, null, $post_id );

	}

	if ( $customfield['type'] == 'pods-field' ) {

		$pod = pods( $customfield['pods_group'], $post_id );

		$data[ $customfield['pods_field'] ] = $_POST[ $customfield['pods_field'] ];
		$pod->save( $data, null, $post_id );
	}
}


add_filter( 'buddyforms_formbuilder_fields_options', 'buddyforms_pods_formbuilder_fields_options', 10, 4 );
function buddyforms_pods_formbuilder_fields_options( $form_fields, $field_type, $field_id, $form_slug = '' ) {
	global $buddyforms;

	if ( $field_type == 'pods-group' || $field_type == 'pods-field') {
		return $form_fields;
	}

	if( ! defined('PODS_VERSION')){
		return $form_fields;
	}

	$post_type = $buddyforms[ $form_slug ]['post_type'];

	$pods = pods_api()->load_pods( array( 'fields' => false ) );

	$pod_form_fields = array();
	$pods_list       = array();
	foreach ( $pods as $pod_key => $pod ) {
		$pods_list[ $pod['id'] ] = $pod['name'];
		$pod_form_fields[ $pod['name'] ][ 'none' ] = 'Select a field';


		foreach ( $pod['fields'] as $pod_fields_key => $field ) {
			$pod_form_fields[ $pod['name'] ][ $pod_fields_key ] = $field['name'];
		}
	}

	if( ! isset($pod_form_fields[ $post_type ])){
		return $form_fields;
	}

	if ( isset( $pod_form_fields[ $post_type ] ) ) {
		$mapped_pods_field = isset( $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['mapped_pods_field'] ) ? $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['mapped_pods_field'] : '';
	}
	$form_fields['PODS']['mapped_pods_field'] = new Element_Select( '<b>' . __( 'Map with existing Pods Field', 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][mapped_pods_field]", $pod_form_fields[ $post_type ], array(
		'value'    => $mapped_pods_field,
		'class'    => 'bf_tax_select',
		'field_id' => $field_id,
		'id'       => 'buddyforms_pods_' . $field_id,
	) );


	return $form_fields;
}

add_action( 'buddyforms_process_submission_end', 'buddyforms_pods_process_submission_end', 10, 1 );
function buddyforms_pods_process_submission_end( $args ) {
	global $buddyforms;

	extract( $args );

	if ( ! isset( $post_id ) ) {
		return;
	}

	if ( isset( $buddyforms[ $form_slug ] ) ) {
		if ( isset( $buddyforms[ $form_slug ]['form_fields'] ) ) {

			foreach ( $buddyforms[ $form_slug ]['form_fields'] as $field_key => $field ) {

				if ( isset( $field['mapped_pods_field'] ) && $field['mapped_pods_field'] != 'none' ) {

					$pod = pods( $buddyforms[ $form_slug ]['post_type'], $post_id );
					$data[ $field['mapped_pods_field'] ] = $_POST[ $field['slug'] ];
					$pod->save( $data, null, $post_id );

				}

			}
		}
	}

}


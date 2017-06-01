<?php
	if ( ! function_exists( 'array_to_select' ) ) {
		/**
		 * Convert an array to a select dropdown
		 * @param  array  $args
		 *         			initial     string    The initial value
		 *         			name        string    The name attribute of the select box
		 *         			class       string    A css class fro the select box
		 *         			id          string    An id for the select box
		 *         			selected    string    The value that should be selected
		 *         			return      boolean   Return the output instead of echo
		 * @return string       returned or echoed
		 */
		function array_to_select( array $args ) {

			if ( empty( $args['data'] ) || empty ( $args['name'] ) )
				return;

			if ( ! empty( $args['initial'] ) ) {
				$initial = $args['initial'];
			} else {
				$initial = "Select item...";
			}

			$output = '<select name="'. $args['name']  .'" class="'. ( isset ( $args['class'] ) ? $args['class'] : '' ) .'" id="'. ( isset ( $args['id'] ) ? $args['id'] : '' ) .'">';

			$output .= '<option '. ( ! isset ( $args['selected'] ) ? ' selected ' : '' ) .' disabled value="NULL">'.$initial.'</option>';

			foreach ( $args['data'] as $value=>$name ) {

				if ( isset ( $args['selected'] ) && $value == $args['selected'] ) {
					$selected = ' selected ';
				} else {
					$selected = '';
				}

				$output .= '<option '. $selected .' value="'.$value.'">'. $name .'</option>';

			}

			$output .= '</select>';

			if ( ! isset( $args['return'] ) || $args['return'] != true ) {
				echo $output;
			} else {
				return $output;
			}

		}
	}

	if ( ! function_exists( 'print_var' ) ) {
		/**
		 * Function to output a formatted version of print_r()
		 * @param  mixed   $var    Mixed input that should be expanded.
		 * @param  boolean $return Return instead of echo
		 * @return string          Return or echo
		 */
		function print_var( $var, $return = false ) {
			$output = '<pre>'.print_r( $var, true ).'</pre>';

			if ( $return == false ) {
				echo $output;
			} else {
				return $output;
			}
		}
	}
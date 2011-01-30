<?php
/**
 * @package Habari
 *
 */

/**
 * XMLRPC Utility class
 *
 */
class XMLRPCUtils
{

	/**
	 * Encode a variable value into the parameters of the XML tree
	 *
	 * @param SimpleXMLElement $params The parameter to add the value elements to.
	 * @param mixed $arg The value to encode
	 */
	public static function encode_arg( $params, $arg )
	{
		switch ( true ) {
			case is_array( $arg ):
				$data = $params->addchild( 'value' )->addchild( 'array' )->addchild( 'data' );
				foreach ( $arg as $element ) {
					self::encode_arg( $data, $element );
				}
				break;
			case ( $arg instanceof XMLRPCDate ):
				$params->addchild( 'value' )->addchild( 'dateTime.iso8601', date( 'c', $arg->date ) );
				break;
			case ( $arg instanceof XMLRPCBinary ):
				$params->addchild( 'value' )->addchild( 'base64', base64_encode( $arg->data ) );
				break;
			case ( $arg instanceof XMLRPCStruct ):
				$struct = $params->addchild( 'value' )->addchild( 'struct' );
				$object_vars = $arg->get_fields();
				foreach ( $object_vars as $field ) {
					$member = $struct->addchild( 'member' );
					$member->addchild( 'name', $field );
					self::encode_arg( $member, $arg->$field );
				}
				break;
			case is_object( $arg ):
				$struct = $params->addchild( 'value' )->addchild( 'struct' );
				$object_vars = get_object_vars( $arg );
				foreach ( $object_vars as $key=>$value ) {
					$member = $struct->addchild( 'member' );
					$member->addchild( 'name', $key );
					self::encode_arg( $member, $value );
				}
				break;
			case is_integer( $arg ):
				$params->addchild( 'value' )->addchild( 'i4', $arg );
				break;
			case is_bool( $arg ):
				$params->addchild( 'value' )->addchild( 'boolean', $arg ? '1' : '0' );
				break;
			case is_string( $arg ):
				$params->addchild( 'value' )->addchild( 'string', $arg );
				break;
			case is_float( $arg ):
				$params->addchild( 'value' )->addchild( 'double', $arg );
				break;
		}
	}
	
	/**
	 * Decode the value of a response parameter using the datatype specified in the XML element.
	 *
	 * @param SimpleXMLElement $value A "value" element from the XMLRPC response
	 * @return mixed The value of the element, decoded from the datatype specified in the xml element
	 */
	public static function decode_args( $value )
	{
		if ( count( $value->children() ) ) {
			$value = $value->xpath( '*' );
			$value = $value[0];
		}
		switch ( $value->getName() ) {
			case 'data':
			case 'array':
				$result_array = array();
				foreach ( $value->xpath( '//data/value' ) as $array_value ) {
					$result_array[] = self::decode_args( $array_value );
				}
				return $result_array;
				break;
			case 'struct':
				$result_struct = new XMLRPCStruct();
				foreach ( $value->xpath( '//member' ) as $struct_value ) {
					$property_name = (string) $struct_value->name;
					$children = $struct_value->value->children();
					if ( count( $children ) > 0 ) {
						$result_struct->$property_name = self::decode_args( $children[0] );
					}
					else {
						$result_struct->$property_name = (string) $struct_value->value;
					}
				}
				return $result_struct;
				break;
			case 'string':
				return (string) $value;
			case 'i4':
			case 'integer':
				return (int) $value;
			case 'double':
				return (double) $value;
			case 'boolean':
				return ( (int) $value == 1 ) ? true : false;
			case 'dateTime.iso8601':
				return strtotime( (string) $value );
			default:
				return (string) $value;
		}
	}

}

?>

<?php
/*
 * Copyright 2010 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

if ( ! class_exists( 'IGDGoogle_Client' ) ) {
	require_once dirname( __FILE__ ) . '/../autoload.php';
}

/**
 * This class implements the RESTful transport of apiServiceRequest()'s
 */
class IGDGoogle_Http_REST {
	/**
	 * Executes a IGDGoogle_Http_Request and (if applicable) automatically retries
	 * when errors occur.
	 *
	 * @param IGDGoogle_Client $client
	 * @param IGDGoogle_Http_Request $req
	 *
	 * @return array decoded result
	 * @throws IGDGoogle_Service_Exception on server side error (ie: not authenticated,
	 *  invalid or malformed post body, invalid url)
	 */
	public static function execute( IGDGoogle_Client $client, IGDGoogle_Http_Request $req ) {
		$runner = new IGDGoogle_Task_Runner(
			$client,
			sprintf( '%s %s', $req->getRequestMethod(), $req->getUrl() ),
			array( get_class(), 'doExecute' ),
			array( $client, $req )
		);

		return $runner->run();
	}

	/**
	 * Executes a IGDGoogle_Http_Request
	 *
	 * @param IGDGoogle_Client $client
	 * @param IGDGoogle_Http_Request $req
	 *
	 * @return array decoded result
	 * @throws IGDGoogle_Service_Exception on server side error (ie: not authenticated,
	 *  invalid or malformed post body, invalid url)
	 */
	public static function doExecute( IGDGoogle_Client $client, IGDGoogle_Http_Request $req ) {
		$httpRequest = $client->getIo()->makeRequest( $req );
		$httpRequest->setExpectedClass( $req->getExpectedClass() );

		return self::decodeHttpResponse( $httpRequest, $client );
	}

	/**
	 * Decode an HTTP Response.
	 * @static
	 *
	 * @param IGDGoogle_Http_Request $response The http response to be decoded.
	 * @param IGDGoogle_Client $client
	 *
	 * @return mixed|null
	 * @throws IGDGoogle_Service_Exception
	 */
	public static function decodeHttpResponse( $response, IGDGoogle_Client $client = null ) {
		$code    = $response->getResponseHttpCode();
		$body    = $response->getResponseBody();
		$decoded = null;

		if ( ( intVal( $code ) ) >= 300 ) {
			$decoded = json_decode( $body, true );
			$err     = 'Error calling ' . $response->getRequestMethod() . ' ' . $response->getUrl();
			if ( isset( $decoded['error'] ) &&
			     isset( $decoded['error']['message'] ) &&
			     isset( $decoded['error']['code'] ) ) {
				// if we're getting a json encoded error definition, use that instead of the raw response
				// body for improved readability
				$err .= ": ({$decoded['error']['code']}) {$decoded['error']['message']}";
			} else {
				$err .= ": ($code) $body";
			}

			$errors = null;
			// Specific check for APIs which don't return error details, such as Blogger.
			if ( isset( $decoded['error'] ) && isset( $decoded['error']['errors'] ) ) {
				$errors = $decoded['error']['errors'];
			}

			$map = null;
			if ( $client ) {
				$client->getLogger()->error(
					$err,
					array( 'code' => $code, 'errors' => $errors )
				);

				$map = $client->getClassConfig(
					'IGDGoogle_Service_Exception',
					'retry_map'
				);
			}

			//new IGDGoogle_Service_Exception($err, $code, null, $errors, $map);
			error_log( 'REST.php: ' . $err . ' ' . $code . ' Line: ' . __LINE__ );

		}

		// Only attempt to decode the response, if the response code wasn't (204) 'no content'
		if ( $code != '204' ) {
			if ( $response->getExpectedRaw() ) {
				return $body;
			}

			$decoded = json_decode( $body, true );
			if ( $decoded === null || $decoded === "" ) {
				$error = "Invalid json in service response: $body";
				if ( $client ) {
					$client->getLogger()->error( $error );
				}
				throw new IGDGoogle_Service_Exception( $error );
			}

			if ( $response->getExpectedClass() ) {
				$class   = $response->getExpectedClass();
				$decoded = new $class( $decoded );
			}
		}

		return $decoded;
	}

	/**
	 * Parse/expand request parameters and create a fully qualified
	 * request uri.
	 * @static
	 *
	 * @param string $servicePath
	 * @param string $restPath
	 * @param array $params
	 *
	 * @return string $requestUrl
	 */
	public static function createRequestUri( $servicePath, $restPath, $params ) {
		$requestUrl      = $servicePath . $restPath;
		$uriTemplateVars = array();
		$queryVars       = array();
		foreach ( $params as $paramName => $paramSpec ) {
			if ( $paramSpec['type'] == 'boolean' ) {
				$paramSpec['value'] = ( $paramSpec['value'] ) ? 'true' : 'false';
			}
			if ( $paramSpec['location'] == 'path' ) {
				$uriTemplateVars[ $paramName ] = $paramSpec['value'];
			} else if ( $paramSpec['location'] == 'query' ) {
				if ( isset( $paramSpec['repeated'] ) && is_array( $paramSpec['value'] ) ) {
					foreach ( $paramSpec['value'] as $value ) {
						$queryVars[] = $paramName . '=' . rawurlencode( rawurldecode( $value ) );
					}
				} else {
					$queryVars[] = $paramName . '=' . rawurlencode( rawurldecode( $paramSpec['value'] ) );
				}
			}
		}

		if ( count( $uriTemplateVars ) ) {
			$uriTemplateParser = new IGDGoogle_Utils_URITemplate();
			$requestUrl        = $uriTemplateParser->parse( $requestUrl, $uriTemplateVars );
		}

		if ( count( $queryVars ) ) {
			$requestUrl .= '?' . implode( '&', $queryVars );
		}

		return $requestUrl;
	}
}

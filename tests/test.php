<?php 

use PHPUnit\Framework\TestCase; 

require_once( 'lib/Http.php' );
require_once( 'optune.php' );

// Let's test CURL wrapper
class Simplify_HTTPTest extends TestCase
{
	protected $test_url;
	protected $test_params;

	// Setup test enviroment
	protected function setUp()
	{
		$this->test_username = 'Simon-Kwe';
		$this->test_params = array( 'test1' => 'test2', 'test3' => 'test4' );
	}

	// Test GET request
	public function testGet()
	{ 
		$web = new Simplify_HTTP();

		$response = $web->apiRequest( $this->test_username, 'GET' );
		if( is_array( $response ) && count( $response) > 0 )
		{
			foreach( $response as $el )
			{
				$this->assertArrayHasKey( 'playDate', $el );
			}
		}
	}

	// Test POST request
	public function testPost()
	{ 
		$web = new Simplify_HTTP();

		$response = $web->apiRequest( $this->test_username, 'POST', $this->test_params );
		if( is_array( $response ) && count( $response) > 0 )
		{
			foreach( $response as $el )
			{
				$this->assertArrayHasKey( 'playDate', $el );
			}
		}
	}

}

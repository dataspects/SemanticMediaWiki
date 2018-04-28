<?php

namespace SMW\Tests\MediaWiki\Search;

use SMW\MediaWiki\Search\QueryBuilder;

/**
 * @covers \SMW\MediaWiki\Search\QueryBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class QueryBuilderTest extends \PHPUnit_Framework_TestCase {

	private $webRequest;
	private $store;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			QueryBuilder::class,
			new QueryBuilder( $this->webRequest )
		);
	}

	public function testGetQuery() {

		$instance = new QueryBuilder(
			$this->webRequest
		);

		$this->assertNull(
			$instance->getQuery( 'Foo' )
		);

		$this->assertInstanceOf(
			'\SMWQuery',
			$instance->getQuery( '[[Foo::bar]]' )
		);
	}

	public function testAddNamespaceCondition() {

		$this->webRequest->expects( $this->any() )
			->method( 'getCheck' )
			->with($this->equalTo( 'ns6' ) )
			->will( $this->returnValue( true ) );

		$instance = new QueryBuilder(
			$this->webRequest
		);

		$description = $this->getMockBuilder( '\SMW\Query\Language\ThingDescription' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getDescription' )
			->will( $this->returnValue( $description ) );

		$query->expects( $this->once() )
			->method( 'setDescription' );

		$instance->addNamespaceCondition( $query, [ 6 => true ] );
	}

	public function testAddSort() {

		$this->webRequest->expects( $this->any() )
			->method( 'getVal' )
			->with($this->equalTo( 'sort' ) )
			->will( $this->returnValue( 'recent' ) );

		$instance = new QueryBuilder(
			$this->webRequest
		);

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'setSortKeys' );

		$instance->addSort( $query );
	}

	public function testGetQueryString_EmptyFieldValues_ReturnsTermOnly() {

		$instance = new QueryBuilder(
			$this->webRequest,
			[ 'foo' ]
		);

		$instance->noCheck();

		$this->assertEquals(
			'Foo',
			$instance->getQueryString( $this->store, 'Foo' )
		);
	}

	public function testGetQueryString_FormFieldValues() {

		$form_def = [
			'forms' => [
				'Foo' => [
					'Bar property'
				]
			]
		];

		$this->webRequest->expects( $this->at( 0 ) )
			->method( 'getVal' )
			->with( $this->equalTo( 'smw-form' ) )
			->will( $this->returnValue( 'foo' ) );

		$this->webRequest->expects( $this->at( 1 ) )
			->method( 'getArray' )
			->with( $this->equalTo( 'barproperty' ) )
			->will( $this->returnValue( [ 'Foobar' ] ) );

		$instance = new QueryBuilder(
			$this->webRequest,
			$form_def
		);

		$instance->noCheck();

		$this->assertEquals(
			'<q>[[Bar property::Foobar]]</q>  Foo',
			$instance->getQueryString( $this->store, 'Foo' )
		);
	}

	public function testGetQueryString_DifferentFormsFieldValues() {

		$form_def = [
			'forms' => [
				'Foo-1' => [
					'Bar property'
				],
				'Foo-2' => [
					'Bar property'
				]
			]
		];

		$this->webRequest->expects( $this->at( 0 ) )
			->method( 'getVal' )
			->with( $this->equalTo( 'smw-form' ) )
			->will( $this->returnValue( 'foo-2' ) );

		$this->webRequest->expects( $this->at( 1 ) )
			->method( 'getArray' )
			->with( $this->equalTo( 'barproperty' ) )
			->will( $this->returnValue( [ '', 42 ] ) );

		$instance = new QueryBuilder(
			$this->webRequest,
			$form_def
		);

		$instance->noCheck();

		$this->assertEquals(
			'<q>[[Bar property::42]]</q>  Foo',
			$instance->getQueryString( $this->store, 'Foo' )
		);
	}

	public function testGetQueryString_OpenFormFieldValues() {

		$form_def = [
			'forms' => [
				'open'
			]
		];

		$this->webRequest->expects( $this->at( 0 ) )
			->method( 'getVal' )
			->with( $this->equalTo( 'smw-form' ) )
			->will( $this->returnValue( 'open' ) );

		$this->webRequest->expects( $this->at( 1 ) )
			->method( 'getArray' )
			->with( $this->equalTo( 'property' ) )
			->will( $this->returnValue(  [ 'Bar' ] ) );

		$this->webRequest->expects( $this->at( 2 ) )
			->method( 'getArray' )
			->with( $this->equalTo( 'pvalue' ) )
			->will( $this->returnValue( [ 42 ] ) );

		$this->webRequest->expects( $this->at( 3 ) )
			->method( 'getArray' )
			->with( $this->equalTo( 'op' ) )
			->will( $this->returnValue( [ 'OR' ] ) );

		$instance = new QueryBuilder(
			$this->webRequest,
			$form_def
		);

		$instance->noCheck();

		$this->assertEquals(
			'<q>[[Bar::42]] </q> OR Foo',
			$instance->getQueryString( $this->store, 'Foo' )
		);
	}

	/**
	 * @dataProvider termProvider
	 */
	public function testTerm_parser( $term, $expected ) {

		$this->assertEquals(
			$expected,
			QueryBuilder::term_parser( $term )
		);
	}

	public function termProvider() {

		yield [
			'in:foo',
			'[[in:foo]]'
		];

		yield [
			'[[in:foo]]',
			'[[in:foo]]'
		];

		yield [
			'in:foo || bar',
			'[[in:foo]] || bar'
		];

		yield [
			'in:foo && bar',
			'[[in:foo]] && bar'
		];

		yield [
			'in:foo || in:bar',
			'[[in:foo]] || [[in:bar]]'
		];

		yield [
			'in:foo bar in:bar ',
			'[[in:foo bar]] [[in:bar]]'
		];

		yield [
			'in:foo bar && in:bar',
			'[[in:foo bar]] && [[in:bar]]'
		];

		yield [
			'in:foo bar || in:bar ',
			'[[in:foo bar]] || [[in:bar]]'
		];

		yield [
			'(in:foo bar && in:foo) || in:bar ',
			'<q>[[in:foo bar]] && [[in:foo]]</q> || [[in:bar]]'
		];

		yield [
			'in:foo bar in:bar phrase:foobar 123 && in:oooo',
			'[[in:foo bar]] [[in:bar]] [[phrase:foobar 123]] && [[in:oooo]]'
		];

		yield [
			'<q>in:foo bar && in:bar</q> OR phrase:foo bar foobar',
			'<q>[[in:foo bar]] && [[in:bar]]</q> OR [[phrase:foo bar foobar]]'
		];

		yield [
			'(in:foo && in:bar)||in:foobar',
			'<q>[[in:foo]] && [[in:bar]]</q> || [[in:foobar]]'
		];

		yield [
			'(in:foo && (in:bar AND not:ooo)) || in:foobar',
			'<q>[[in:foo]] && <q>[[in:bar]] AND [[not:ooo]]</q></q> || [[in:foobar]]'
		];


		yield [
			'<q>in:foo bar && in:bar</q> OR [[Has number::123]]',
			'<q>[[in:foo bar]] && [[in:bar]]</q> OR [[Has number::123]]'
		];
	}

}

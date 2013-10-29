<?php
/**
 * DbExpression class file.
 *
 * @link http://www.php.com/
 * @license http://www.php.com/license
 */


/**
 * DbExpression represents a DB expression that does not need escaping.
 * DbExpression is mainly used with SqlMagic as attribute values. When inserting or updating a database record,
 * attribute values of type DbExpression will be directly put into the corresponding SQL statement without escaping.
 * A typical usage is that an attribute is set with 'NOW()' expression so that saving the record would fill the corresponding column with the current DB server timestamp.
 * <code>
 * Q::loadCore('db/DbExpression');
 * $usr = new User;
 * $usr->create_date = new DbExpression('NOW()');
 * //$usr->create_date = 'NOW()';  will insert the date as a string 'NOW()' as it is escaped.
 * Q::db()->insert($usr);
 * </code>
 *
 * @version $Id: DbExpression.php 1000 2009-07-7 18:27:22
 * @package .db
 * @since 1.0
 */
class DbExpression {

    private $expression;

	/**
	 * Skip parameter binding on values.
	 * @var bool
	 */
	public $skipBinding;

	/**
	 * Use OR statement instead of AND
	 * @var bool
	 */
	public $useOrStatement;

    function  __construct($expression, $useOrStatement=FALSE, $skipBinding=FALSE) {
        $this->expression = $expression;
		$this->useOrStatement = $useOrStatement;
		$this->skipBinding = $skipBinding;
    }

    function  __toString() {
        return $this->expression;
    }
}

<?php

namespace DataGrid;
use Nette;

/**
 * Representation of data grid action.
 *
 * @author     Roman Sklenář
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    New BSD License
 * @example    http://addons.nette.org/datagrid
 * @package    Nette\Extras\DataGrid
 */
class Action extends \Nette\ComponentModel\Component implements IAction
{
	/**#@+ special action key */
	const WITH_KEY		= TRUE;
	const WITHOUT_KEY	= FALSE;
	/**#@-*/

	/** @var Nette\Utils\Html  action element template */
	protected $html;

	/** @var string */
	static public $ajaxClass = 'datagrid-ajax';

	/** @var string */
	public $destination;

	/** @var bool|string */
	public $key;

	/** @var Nette\Callback|Closure	function($data) */
	public $ifDisableCallback;


	/**
	 * Data grid action constructor.
	 * @note   for full ajax support, destination should not change module,
	 * @note   presenter or action and must be ended with exclamation mark (!)
	 *
	 * @param  string  textual title
	 * @param  string|array  textual link destination or array($link, 'key' => 'value', ...)
	 * @param  Nette\Web\Html    element which is added to a generated link
	 * @param  bool    use ajax? (add class self::$ajaxClass into generated link)
	 * @param  mixed   generate link with argument? (if yes you can specify name of parameter
	 * 				   otherwise variable DataGrid\DataGrid::$keyName will be used and must be defined)
	 * @return void
	 */
	public function __construct($title, $destination, Nette\Utils\Html $icon = NULL, $useAjax = FALSE, $key = self::WITH_KEY)
	{
		parent::__construct();
		$this->destination = $destination;
		$this->key = $key;
		
		$a = Nette\Utils\Html::el('a')->title($title);
		if ($useAjax) $a->addClass(self::$ajaxClass);

		if ($icon !== NULL && $icon instanceof Nette\Utils\Html) {
			$a->add($icon);
		} else {
			$a->setText($title);
		}
		$this->html = $a;
	}


	/**
	 * Generates action's link. (use before data grid is going to be rendered)
	 * @return void
	 */
	public function generateLink(array $args = NULL)
	{
		$customArgs = $this->destination;
		if(is_array($customArgs)) {
			$destination = array_shift($customArgs);
			$args = array_merge($args === null ? array() : $args, $customArgs);
		} else {
			$destination = $customArgs;
		}
		
		$dataGrid = $this->lookup('DataGrid\DataGrid', TRUE);
		$control = $dataGrid->lookup('\Nette\Application\UI\Control', TRUE);

		if($this->key != self::WITHOUT_KEY) {
			$key = $this->key == NULL || is_bool($this->key) ? $dataGrid->keyName : $this->key;
			$args = array_merge(array($key => $args[$dataGrid->keyName]), $args);
		}
		
		$link = $control->link($destination, $args);

		$this->html->href($link);
	}



	/********************* interface DataGrid\IAction *********************/



	/**
	 * Gets action element template.
	 * @return Nette\Utils\Html
	 */
	public function getHtml()
	{
		return $this->html;
	}

}

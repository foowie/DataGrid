<?php

namespace DataGrid;

/**
 * @author Daniel Robenek <danrob@seznam.cz>
 */
class CallbackAction extends \Nette\ComponentModel\Component implements IAction {

	/** @var Nette\Utils\Html  action element template */
	protected $html;

	/** @var string */
	static public $ajaxClass = 'datagrid-ajax';

	/** @var \Nette\Callback */
	public $linkCallback;

	/** @var Nette\Callback|Closure */
	public $ifDisableCallback;

	/**
	 *
	 * @param type $title
	 * @param type $linkCallback function($data) returns url
	 * @param Nette\Utils\Html $icon
	 * @param type $useAjax 
	 */
	public function __construct($title, $linkCallback, Nette\Utils\Html $icon = NULL, $useAjax = FALSE) {
		parent::__construct();

		$this->linkCallback = callback($linkCallback);
		$a = \Nette\Utils\Html::el('a')->title($title);
		if ($useAjax)
			$a->addClass(self::$ajaxClass);

		if ($icon !== NULL && $icon instanceof \Nette\Utils\Html) {
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
	public function generateLink(array $args = NULL, array $data = null) {
		$link = $this->linkCallback->invokeArgs(array($data));
		$this->html->href($link);
	}

	/*	 * ******************* interface DataGrid\IAction ******************** */

	/**
	 * Gets action element template.
	 * @return Nette\Utils\Html
	 */
	public function getHtml() {
		return $this->html;
	}

}

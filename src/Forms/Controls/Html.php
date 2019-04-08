<?php
/**
 * Created by IntelliJ IDEA.
 * User: stopka
 * Date: 9.3.15
 * Time: 15:47
 */

namespace Stopka\NetteFormRenderer\Forms\Controls;


use Nette\Forms\Controls\BaseControl;
use Nette\Utils\Html as HtmlUtil;

class Html extends BaseControl
{
    /**
     * @param string|object  label
     * @param HtmlUtil html
     */
    public function __construct($label = null, ?HtmlUtil $html = null)
    {
        parent::__construct($label);
        if (!$html) {
            $html = HtmlUtil::el();
        }
        $this->setHtml(
            HtmlUtil::el('div')->setHtml($html)
        );
        $this->setOmitted();
    }

    public function setHtml(HtmlUtil $html)
    {
        $this->control->setName($html->getName());
        $this->control->setHtml($html->getHtml());
        $this->control->addClass("form-html-control");
    }
}

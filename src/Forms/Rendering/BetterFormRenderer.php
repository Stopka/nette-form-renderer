<?php

namespace Stopka\Nette\Forms\Rendering;

use Nette;
use Nette\Forms\Controls;
use Nette\Utils\Html as Html;

class BetterFormRenderer implements Nette\Forms\IFormRenderer {
    use Nette\SmartObject;

    /**
     *  /--- form.container
     *
     *    /--- error.container
     *      .... error.item [.class]
     *      .... error.icon [.class]
     *    \---
     *
     *    /--- hidden.container
     *      .... HIDDEN CONTROLS
     *    \---
     *
     *    /--- group.container
     *      .... group.label
     *      .... group.description
     *
     *      /--- controls.container
     *
     *        /--- pair.container [.required .optional .odd]
     *
     *          /--- label.container
     *            .... LABEL
     *            .... label.suffix
     *            .... label.requiredsuffix
     *          \---
     *
     *          /--- control.container [.odd]
     *            .... CONTROL [.required .text .password .file .submit .button]
     *            .... control.requiredsuffix
     *            .... control.description
     *            .... control.errorcontainer + control.erroritem + control.erroricon
     *          \---
     *        \---
     *      \---
     *    \---
     *  \--
     *
     * @var array of HTML tags
     */
    public $wrappers = array(
        'form' => array(
            'container' => null,
        ),
        'error' => array(
            'container' => 'div class="alert alert-error"',
            'icon' => 'i class="fa fa-exclamation-triangle"',
            'item' => 'div',
        ),
        'group' => array(
            'container' => 'fieldset',
            'logicalContainer' => 'div class="form-group"',
            'label' => 'legend',
            'description' => 'div class="description group-description"',
        ),
        'controls' => array(
            'container' => 'div',
        ),
        'pair' => array(
            'container' => 'div class="control-group control-pair"',
            '.required' => 'required',
            '.optional' => null,
            '.odd' => 'odd',
            '.error' => 'has-error',
        ),
        'multi' => array(
            'container' => 'div class="control-group control-multi"',
            '.required' => 'required',
            '.optional' => null,
            '.odd' => 'odd',
            '.error' => 'has-error',
        ),
        'control' => array(
            'container' => 'div class=controls',
            '.odd' => 'odd',
            'description' => 'div class="description control-description"',
            'requiredsuffix' => '',
            'errorcontainer' => 'span class=form-error-message',
            'erroricon' => 'i class="fa fa-exclamation-triangle"',
            'erroritem' => 'span',
            '.required' => 'required',
            '.text' => 'text',
            '.password' => 'text',
            '.file' => 'text',
            '.submit' => 'button',
            '.image' => 'imagebutton',
            '.button' => 'button',
        ),
        'label' => array(
            'container' => 'div class="control-label"',
            'suffix' => null,
            'requiredsuffix' => '',
        ),
        'hidden' => array(
            'container' => 'div class="hidden-controls"',
        ),
    );

    /** @var Nette\Forms\Form */
    protected $form;

    /** @var int */
    protected $counter;

    /** @var  Nette\Forms\ControlGroup[] */
    protected $groups;

    /** @var  Nette\Forms\IControl[] */
    protected $controls;

    /** @var  Controls\Button[] */
    protected $action_buttons;

    /**
     * Provides form rendering.
     * @param  Nette\Forms\Form
     * @param  string 'begin', 'errors', 'ownerrors', 'body', 'end', 'buttons', 'controls', 'group:<name>', 'groupcontrols:<name>', 'pair:<name>', 'control:<name>', 'label:<name>'   or empty to render full form
     * @return string
     */
    public function render(Nette\Forms\Form $form, $mode = NULL): string {
        if ($this->form !== $form) {
            $this->form = $form;
        }
        if (!$mode) {
            return $this->renderDefault();
        }
        return $this->renderMode($mode);
    }

    /**
     * Prepares groups with options embedNext and embed to be rendered
     */
    protected function prepareGroups() {
        $parent = [];
        $counter = [];
        foreach ($this->form->getGroups() as $group) {
            if ($counter && $counter[0] > 0) {
                $embeded = $parent[0]->getOption('embed');
                if (!$embeded) {
                    $embeded = [];
                }
                $embeded[] = $group;
                $parent[0]->setOption('embed', $embeded);
                $counter[0]--;
            }
            $next = $group->getOption('embedNext');
            if ($next === TRUE) {
                $next = 1;
            }
            if ($next) {
                array_unshift($counter, $next);
                array_unshift($parent, $group);
                continue;
            }
            while ($counter && $counter[0] === 0) {
                array_shift($parent);
                array_shift($counter);
            }
        }
    }

    /**
     * Default full rendered form
     * @return string
     */
    public function renderDefault(): string {
        $s = '';
        $s .= $this->renderBegin();
        $s .= $this->renderErrors();
        $s .= $this->renderBody();
        $s .= $this->renderEnd();
        return $s;
    }

    /**
     * Renders specified part of form
     * @param string|Nette\Forms\Container|Nette\Forms\ControlGroup|Nette\Forms\IControl $mode <part> or <part>:<name>
     * @return string
     */
    public function renderMode($mode): string {
        if (is_object($mode)) {
            if ($mode instanceof Nette\Forms\Container) {
                return $this->renderGroupControls($mode);
            }
            if ($mode instanceof Nette\Forms\ControlGroup) {
                return $this->renderGroup($mode);
            }
            if ($mode instanceof Nette\Forms\IControl) {
                return $this->renderControl($mode);
            }
            throw new Nette\InvalidArgumentException('Unsupported form render object mode ' . get_class($mode));
        }
        list($mode, $name) = explode(':', $mode . ':');
        switch (strtolower($mode)) {
            case 'begin':
                return $this->renderBegin();
            case 'ownerrors':
                return $this->renderErrors();
            case 'errors':
                return $this->renderErrors(NULL, FALSE);
            case 'body':
                return $this->renderBody();
            case 'groupcontrols':
                $group = $this->form->getGroup($name);
                return $this->renderGroupControls($group);
            case  'buttons':
                return $this->renderButtons();
            case  'controls':
                return $this->renderControls();
            case 'end':
                return $this->renderEnd();
            case 'pair':
                $control = $this->form[$name];
                return $this->renderPair($control);
            case 'control':
                $control = $this->form[$name];
                return $this->renderControl($control);
            case 'label':
                $control = $this->form[$name];
                return $this->renderLabel($control);
            case 'group':
                $group = $this->form->getGroup($name);
                return $this->renderGroup($group);
            default:
                throw new Nette\InvalidArgumentException("Unsupported form renderer mode '$mode'");
        }
    }


    /**
     * Renders form begin.
     * @return string
     */
    public function renderBegin(): string {
        $this->counter = 0;

        foreach ($this->form->getControls() as $control) {
            $control->setOption('rendered', FALSE);
        }

        if ($this->form->isMethod('get')) {
            $el = clone $this->form->getElementPrototype();
            $query = parse_url($el->action, PHP_URL_QUERY);
            $el->action = str_replace("?$query", '', $el->action);
            $s = '';
            foreach (preg_split('#[;&]#', $query, NULL, PREG_SPLIT_NO_EMPTY) as $param) {
                $parts = explode('=', $param, 2);
                $name = urldecode($parts[0]);
                if (!isset($this->form[$name])) {
                    $s .= Html::el('input', ['type' => 'hidden', 'name' => $name, 'value' => urldecode($parts[1])]);
                }
            }
            return $el->startTag() . ($s ? "\n\t" . $this->getWrapper('hidden container')->setHtml($s) : '');

        } else {
            return $this->form->getElementPrototype()->startTag();
        }
    }


    /**
     * Renders form end.
     * @return string
     */
    public function renderEnd(): string {
        $s = '';
        foreach ($this->form->getControls() as $control) {
            if ($control->getOption('type') === 'hidden' && !$control->getOption('rendered')) {
                $s .= $control->getControl();
            }
        }
        if (iterator_count($this->form->getComponents(TRUE, Nette\Forms\Controls\TextInput::class)) < 2) {
            $s .= '<!--[if IE]><input type=IEbug disabled style="display:none"><![endif]-->';
        }
        if ($s) {
            $s = $this->getWrapper('hidden container')->setHtml($s) . "\n";
        }

        return $s . $this->form->getElementPrototype()->endTag() . "\n";
    }


    /**
     * Renders validation errors (per form or per control).
     * @return string
     */
    public function renderErrors(Nette\Forms\IControl $control = NULL, $own = TRUE): string {
        $errors = $control
            ? $control->getErrors()
            : ($own ? $this->form->getOwnErrors() : $this->form->getErrors());
        if (!$errors) {
            return "";
        }
        $container = $this->getWrapper($control ? 'control errorcontainer' : 'error container');
        $item = $this->getWrapper($control ? 'control erroritem' : 'error item');
        $icon = $this->getWrapper($control ? 'control erroricon' : 'error icon');

        foreach ($errors as $error) {
            $item = clone $item;
            $icon = clone $icon;
            $item->addHtml($icon);
            if ($error instanceof Html) {
                $item->addHtml($error);
            } else {
                $item->addText($error);
            }
            $container->addHtml($item);
        }
        return "\n" . $container->render($control ? 1 : 0);
    }


    /**
     * Renders form body.
     * @return string
     */
    public function renderBody(): string {
        $s = '';

        foreach ($this->form->getGroups() as $group) {
            $s .= $this->renderGroup($group);
        }

        $s .= $this->renderGroupControls($this->form);

        $container = $this->getWrapper('form container');
        $container->setHtml($s);
        return $container->render(0);
    }

    /**
     * Renders group of controls.
     * @param  Nette\Forms\Container|Nette\Forms\ControlGroup $group
     * @return string
     */
    public function renderGroup($group): string {
        if ($group->getOption('rendered')) {
            return '';
        }
        if (!$group->getOption('visual') && !$group->getOption('logical')) {
            return $this->renderGroupControls($group);
        }
        $logical = $group->getOption('logical');
        $defaultContainer = $this->getWrapper($logical ? 'group logicalContainer' : 'group container');
        $translator = $this->form->getTranslator();

        $s = '';

        //Group container
        $container = $group->getOption('container', $defaultContainer);
        $container = $container instanceof Html ? clone $container : Html::el($container);

        //Add attributes to container
        $id = $group->getOption('id');
        if ($id) {
            $container->id = $id;
        }
        $class = $group->getOption('class');
        if ($class) {
            $container->addClass($class);
        }

        //Group container start
        $s .= "\n" . $container->startTag();

        //Group label
        if (!$logical) {
            $text = $group->getOption('label');
            $group_label = $this->getWrapper('group label');
            if ($text instanceof Html) {
                $group_label->addHtml($text);
            } elseif (is_string($text)) {
                if ($translator !== NULL) {
                    $text = $translator->translate($text);
                }
                $group_label->setText($text);
            }
            if ($text) {
                $s .= "\n" . $group_label . "\n";
            }
        }

        //Group description
        $text = $group->getOption('description');
        if ($text instanceof Html) {
            $s .= $text;

        } elseif (is_string($text) || is_array($text)) {
            if ($translator !== NULL) {
                $text = $translator->translate($text);
            }
            $s .= $this->getWrapper('group description')->setText($text) . "\n";
        }

        //Group controls
        $s .= $this->renderGroupControls($group);

        //Group embed group
        if ($group->getOption('embedNext') && !$group->getOption('embed')) {
            $this->prepareGroups();
        }
        if ($embed_group_names = $group->getOption('embed')) {
            if (is_string($embed_group_names)) {
                $embed_group_names = [$embed_group_names];
            }
            foreach ($embed_group_names as $embed_group_name) {
                if (is_string($embed_group_name)) {//pokud místo jména byla přijata přímo skupina
                    $embed_group = $this->form->getGroup($embed_group_name);
                } else {
                    $embed_group = $embed_group_name;
                }

                if (!$embed_group) {
                    continue;
                }
                if ($embed_group == $group) {
                    throw new Nette\InvalidArgumentException('Group can\'t embed itself');
                }
                $s .= $this->renderGroup($embed_group);
            }
        }

        //Group container end
        $s .= $container->endTag() . "\n";
        $group->setOption('rendered', true);
        return $s;
    }


    /**
     * Renders controls of group.
     * @param  Nette\Forms\Container|Nette\Forms\ControlGroup
     * @return string
     */
    public function renderGroupControls($parent): string {
        if (!($parent instanceof Nette\Forms\Container || $parent instanceof Nette\Forms\ControlGroup)) {
            throw new Nette\InvalidArgumentException('Argument must be Nette\Forms\Container or Nette\Forms\ControlGroup instance.');
        }
        if (!$parent->getControls()) {
            return '';
        }

        $container = $this->getWrapper('controls container');

        $buttons = NULL;
        foreach ($parent->getControls() as $control) {
            if ($control->getOption('rendered') || $control->getOption('type') === 'hidden' || $control->getForm(FALSE) !== $this->form) {
                continue;
            }
            if ($control->getOption('type') === 'button') {
                $buttons[] = $control;
                continue;
            }
            if ($buttons) {
                $container->addHtml($this->renderPairMulti($buttons));
                $buttons = NULL;
            }
            $container->addHtml($this->renderPair($control));
        }
        if ($buttons) {
            $additional_class = $parent instanceof Nette\Forms\Container ? 'form-actions' : NULL;
            $action_buttons = $this->renderPairMulti($buttons, $additional_class);
            $container->addHtml($action_buttons);
        }

        $s = '';
        if (count($container)) {
            $s .= "\n" . $container . "\n";
        }

        return $s;
    }


    /**
     * Renders single visual row.
     * @return string
     */
    public function renderPair(Nette\Forms\IControl $control): string {
        $pair = $this->getWrapper('pair container');
        $pair->addHtml($this->renderLabel($control));
        $pair->addHtml($this->renderControl($control));
        $pair->class($this->getValue($control->isRequired() ? 'pair .required' : 'pair .optional'), TRUE);
        $pair->class($control->hasErrors() ? $this->getValue('pair .error') : NULL, TRUE);
        $pair->class($control->getOption('class'), TRUE);
        if (++$this->counter % 2) {
            $pair->class($this->getValue('pair .odd'), TRUE);
        }
        $pair->id = $control->getOption('id');
        return $pair->render(0);
    }


    /**
     * Renders single visual row of multiple controls.
     * @param  Nette\Forms\IControl[]
     * @param string|NULL $additional_class třída, která se má přidat kontejneru
     * @return string
     */
    public function renderPairMulti(array $controls, $additional_class = NULL): string {
        $s = [];
        $pair_id = null;
        $pair_classes = [];
        if ($additional_class) {
            $pair_classes[] = $additional_class;
        }
        foreach ($controls as $control) {
            if (!$control instanceof Nette\Forms\IControl) {
                throw new Nette\InvalidArgumentException('Argument must be array of Nette\Forms\IControl instances.');
            }
            $description = $control->getOption('description');
            if ($description instanceof Html) {
                $description = ' ' . $control->getOption('description');

            } elseif (is_string($description) || is_array($description)) {
                if ($control instanceof Nette\Forms\Controls\BaseControl) {
                    $description = is_array($description) ? $control->translate([$description])[0] : $description;
                }
                $description = ' ' . $this->getWrapper('control description')->setText($description);

            } else {
                $description = '';
            }

            $control->setOption('rendered', TRUE);
            $el = $control->getControl();
            if ($el instanceof Html && $el->getName() === 'input') {
                $el->class($this->getValue("control .$el->type"), TRUE);
            }
            $help = $control->getOption('help');
            $s[] = $el . $description;
            //Přidání id a tříd z option
            $class = $control->getOption('class');
            if ($class) {
                $pair_classes[] = $class;
            }
            $id = $control->getOption('id');
            if ($id) {
                $pair_id = $id;
            }
        }
        $pair = $this->getWrapper('multi container');
        if ($pair_id) {
            $pair->setId($pair_id);
        }
        foreach ($pair_classes as $pair_class) {
            $pair->addClass($pair_class);
        }
        $pair->addHtml($this->renderLabel($control));
        $pair->addHtml($this->getWrapper('control container')->setHtml(implode(' ', $s)));
        return $pair->render(0);
    }


    /**
     * Renders 'label' part of visual row of controls.
     * @return string
     */
    public function renderLabel(Nette\Forms\IControl $control): string {
        $suffix = $this->getValue('label suffix') . ($control->isRequired() ? $this->getValue('label requiredsuffix') : '');
        $label = $control->getLabel();
        if ($label instanceof Html) {
            $label->addHtml($suffix);
            if ($control->isRequired()) {
                $label->class($this->getValue('control .required'), TRUE);
            }
        } elseif ($label != NULL) { // @intentionally ==
            $label .= $suffix;
        }
        return $this->getWrapper('label container')->setHtml($label);
    }


    /**
     * Renders 'control' part of visual row of controls.
     * @return string
     */
    public function renderControl(Nette\Forms\IControl $control): string {
        $body = $this->getWrapper('control container');
        if ($this->counter % 2) {
            $body->class($this->getValue('control .odd'), TRUE);
        }

        $description = $control->getOption('description');
        if ($description instanceof Html) {
            $description = ' ' . $description;

        } elseif (is_string($description)) {
            if ($control instanceof Nette\Forms\Controls\BaseControl) {
                $description = $control->translate($description);
            }
            $description = ' ' . $this->getWrapper('control description')->setText($description);

        } elseif (is_array($description)) {
            if ($control instanceof Nette\Forms\Controls\BaseControl) {
                $description = $control->translate([$description])[0];
            }
            $description = ' ' . $this->getWrapper('control description')->setText($description);
        } else {
            $description = '';
        }

        if ($control->isRequired()) {
            $description = $this->getValue('control requiredsuffix') . $description;
        }

        $control->setOption('rendered', TRUE);
        $el = $control->getControl();
        if ($el instanceof Html && $el->getName() === 'input') {
            $el->class($this->getValue("control .$el->type"), TRUE);
        }
        return $body->setHtml($el . $description . $this->renderErrors($control));
    }


    /**
     * @param  string
     * @return Html
     */
    protected function getWrapper($name): Html {
        $data = $this->getValue($name);
        return $data instanceof Html ? clone $data : Html::el($data);
    }


    /**
     * @param  string
     * @return string
     */
    protected function getValue($name): string {
        $name = explode(' ', $name);
        $data = &$this->wrappers[$name[0]][$name[1]];
        return $data;
    }

    public function renderControls(): string {
        $s = '';
        foreach ($this->form->getControls() as $control) {
            if ($control->getOption('rendered')) {
                continue;
            }
            $s .= $this->renderControl($control);
        }
        return $s;
    }

    public function renderButtons(): string {
        throw new Nette\NotImplementedException('Yet unsupported render method');
    }

}
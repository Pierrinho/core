<?php
/**
 * NOVIUS OS - Web OS for digital communication
 *
 * @copyright  2011 Novius
 * @license    GNU Affero General Public License v3 or (at your option) any later version
 *             http://www.gnu.org/licenses/agpl-3.0.html
 * @link http://www.novius-os.org
 */

namespace Nos;

class Renderer_Datetime_Picker extends \Fieldset_Field
{
    protected static $DEFAULT_RENDERER_OPTIONS = array(
        'datepicker' => array(
            'showOn' => 'both',
            'buttonImage' => 'static/novius-os/admin/novius-os/img/icons/date-picker.png',
            'buttonImageOnly' => true,
            'autoSize' => true,
            'timeFormat' => 'HH:mm:ss', // MySQL formatting
            'dateFormat' => 'yy-mm-dd', // MySQL formatting
            'altFormat' => 'dd/mm/yy', // Custom user formatting
            'altTimeFormat' => 'HH:mm', // Custom user formatting
            'altFieldTimeOnly' => false,
            'showButtonPanel' => true,
            'changeMonth' => true,
            'changeYear' => true,
            'showOtherMonths' => true,
            'selectOtherMonths' => true,
            'gotoCurrent' => true,
            'firstDay' => 1,
            'showAnim' => 'slideDown',
        ),
        'wrapper' => '', //'<div class="datepicker-wrapper"></div>',
    );

    /**
     * Standalone build of the media renderer.
     *
     * @param  array  $renderer Renderer definition (attributes + renderer_options)
     * @return string The <input> tag + JavaScript to initialise it
     */
    public static function renderer($renderer = array())
    {
        $renderer['renderer'] = __CLASS__;
        $fieldset = \Fieldset::build_from_config(array(
            $renderer['name'] => $renderer,
        ));
        return $fieldset->field($renderer['name'])->set_template('{field}')->build().$fieldset->build_append();
    }

    protected $options = array();

    public function __construct($name, $label = '', array $renderer = array(), array $rules = array(), \Fuel\Core\Fieldset $fieldset = null)
    {
        list($attributes, $this->options) = static::parseOptions($renderer);
        parent::__construct($name, $label, $attributes, $rules, $fieldset);
    }

    /**
     * How to display the field
     * @return type
     */
    public function build()
    {
        parent::build();
        $attributes = $this->attributes;
        $datepicker_options = $this->options['datepicker'];
        $this->attributes = array();
        $this->set_attribute(array(
            'type' => 'hidden',
            'id' => $attributes['id'],
            'data-datepicker-options' => htmlspecialchars(\Format::forge()->to_json($datepicker_options)),
        ));
        $this->fieldset()->append(static::jsInit($attributes['id'], $this->options));
        $attributes['type'] = 'text';
        $attributes['id'] = ltrim($datepicker_options['altField'], '#');
        unset($attributes['value']);
        $this->set_value(static::processValue($this->value));

        $build = $this->template((string) parent::build());
        // A bit hacky, but can't see another way to keep the template
        $build = str_replace('<input ', '<input '.array_to_attr($attributes).' /><input ', $build);
        // Can't do something else here, since the label's ID is generated (late) within the parent::template() method.
        $build = str_replace('for="'.$this->attributes['id'].'"', 'for="'.$attributes['id'].'"', $build);
        return $build;
    }

    /**
     * Parse the renderer array to get attributes and the renderer options
     * @param  array $renderer
     * @return array 0: attributes, 1: renderer options
     */
    protected static function parseOptions($renderer = array())
    {
        $renderer['type'] = 'hidden';
        $renderer['class'] = (isset($renderer['class']) ? $renderer['class'] : '').' datepicker';

        if (empty($renderer['id'])) {
            $renderer['id'] = uniqid('datepicker_');
        }

        if (empty($renderer['size'])) {
            $renderer['size'] = 20;
        }

        // Default options of the renderer
        $renderer_options = static::$DEFAULT_RENDERER_OPTIONS;

        if (!empty($renderer['renderer_options'])) {
            $renderer_options = \Arr::merge($renderer_options, $renderer['renderer_options']);
        }
        unset($renderer['renderer_options']);

        if (empty($renderer_options['datepicker']['altField'])) {
            $renderer_options['datepicker']['altField'] = '#alt_'.$renderer['id'];
        }

        return array($renderer, $renderer_options);
    }

    /**
     * Generates the JavaScript to initialise the renderer
     *
     * @param   string  HTML ID attribute of the <input> tag
     * @return string JavaScript to execute to initialise the renderer
     */
    protected static function jsInit($id, $renderer_options = array())
    {
        return \View::forge('renderer/datetime_picker', array(
            'id' => $id,
            'wrapper' => \Arr::get($renderer_options, 'wrapper', ''),
        ), false);
    }

    protected static function processValue($value)
    {
        if ($value && $value!='0000-00-00 00:00:00') {
            return \Date::create_from_string($value, 'mysql')->format('%Y-%m-%d %H:%M:%S');
        } else {
            return \Date::forge()->format('%Y-%m-%d %H:%M:%S'); //'%Y-%m-%d %H:%M'
        }
    }

    public function before_save($item, $data)
    {
        $data[$this->name] = \Date::create_from_string($data[$this->name], '%Y-%m-%d %H:%M:%S')->format('mysql');
        return true;
    }

}

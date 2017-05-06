<?php

/**
 * Управление настройками
 */
class WC_Settings_Export_YML extends WC_Settings_Page
{


    public function __construct($id, $sources)
    {

        $this->id = $id;
        $this->label = __('Экспорт в YML', 'woocommerce');
        $this->sources = $sources;


        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_page'), 20);
        add_action('woocommerce_settings_' . $this->id, array($this, 'output'));
        add_action('woocommerce_settings_save_' . $this->id, array($this, 'save'));


        add_action('woocommerce_admin_field_filter_settings', array($this, 'show_filter_settings'));
        add_action('woocommerce_admin_field_yml_head', array($this, 'show_head'));
        add_action('woocommerce_update_option_filter_settings', array($this, 'save_filter_settings'));


        if ($_GET['tab'] == $this->id) {

            wp_register_style($this->id . 'Stylesheet', plugins_url('/style.css', __FILE__));
            wp_enqueue_style($this->id . 'Stylesheet');

            wp_register_script($this->id . 'script', plugins_url('/script.js', __FILE__));
            wp_enqueue_script($this->id . 'script');
        }


        if (isset($_GET['tab']) && $_GET['tab'] == $this->id and isset($_REQUEST['save'])) {
            save_mod_rewrite_rules();
        }


    }


    public function show_filter_settings($value)
    {

        $options = get_option($value['id']);

        $tax = wc_get_attribute_taxonomies();

        ?>
        <table id="filter_settings">
            <?php if ($tax): ?>
                <?php foreach ($tax as $attr):

                    $values = get_terms(wc_attribute_taxonomy_name($attr->attribute_name));

                    if (empty($values))
                        continue;

                    ?>
                    <tr>
                        <td width="225">

                            <strong><?php echo $attr->attribute_label; ?></strong>

                        </td>
                        <td>
                            <select multiple="multiple" style="width:300px"
                                    name="<?= $value['id'] ?>[<?php echo wc_attribute_taxonomy_name($attr->attribute_name); ?>][]">
                                <option value="notfiltered">Не фільтрувати</option>
                                <?php foreach ($values as $_term): ?>
                                    <option
                                        value="<?= $_term->term_id ?>" <?php if (in_array($_term->term_id, (array)$options[wc_attribute_taxonomy_name($attr->attribute_name)])): ?> selected<?php endif; ?> ><?= $_term->name ?></option>
                                <?php endforeach; ?>
                            </select>

                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Поки властивостей немає</p>
            <?php endif; ?>
        </table>
        <?php

    }

    public function save_filter_settings($value)
    {

        $r = $_POST[$value['id']];

        update_option($value['id'], $r);
    }


    public function show_head($value)
    {


        $sources = $this->sources->get_sources();
        $active = (isset($sources[$_GET['source']])) ? $_GET['source'] : 'yandex_market';

        if (get_option('permalink_structure') != '') {

            $yml = home_url('/' . $active . '.xml');
            $yml_gz = home_url('/' . $active . '.xml.gz');

        } else {
            $yml = home_url('/?' . $active . '_export=1');
            $yml_gz = home_url('/?' . $active . '_export=1&gzip=1');
        }


        ?>

        <div id="yml-sources">

            <input type="hidden" name="key_source" value="<?= $active ?>">
            <ul id="yml-sourcelist">
                <?php foreach ($sources as $key => $name): ?>
                    <li <?php if ($key == $active): ?>class="active" <?php endif; ?> >
                        <a href="<?= admin_url('admin.php?page=wc-settings&tab=export_yml') ?>&source=<?= $key ?>"><?= $name ?></a>
                    </li>
                <?php endforeach; ?>
                <li class="separate">|</li>
            </ul>
            <button id="add_source" class="button button-primary" type="submit">Додати джерело</button>
        </div>

        <hr>

        <ul>
            <li>&rarr; <a href="<?= $yml ?>">Посилання на YML файл</a></li>
            <li>&rarr; <a href="<?= $yml_gz ?>">Gzip версія</a></li>
            <li>&infin;&nbsp; <a href="#" id="updateoffers">Оновити всі товари</a>
            </li>
        </ul>

        <div id="ymlprogress"><img src="<?= plugins_url('/ajax-loader.gif', __FILE__) ?>"> <strong>Увага! Під час процесу не закривайте сторінку!</strong></div>

        <hr>


        <?php
    }


    /**
     * Get settings array
     *
     * @return array
     */
    public function get_settings()
    {


        $settings = array(
            array(
                'title' => 'Шапка',
                'type' => 'yml_head',
                'id' => 'yml_head',
                'desc' => ''
            ),
            array('type' => 'sectionend', 'id' => $this->id . '_end_source')

        ); // End pages settings


        $sources = $this->sources->get_sources();


        if ($sources[$_GET['source']]) {
            $id = $_GET['source'];
        } else {
            $id = 'yandex_market';
        }


        $source_settings = $this->source_settings($id);

        $settings = array_merge($settings, $source_settings);


        return apply_filters('woocommerce_' . $this->id . '_settings', $settings);
    }

    /**
     * Get settings array
     *
     * @return array
     */
    public function source_settings($id)
    {


        $sources = $this->sources->get_sources();


        $tax = get_taxonomies(array('object_type' => array('product')), 'objects');

        $tax_brands = array(
            'false' => 'Не вибрано'
        );

        foreach ($tax as $key => $tax_val) {
            if ($key == 'product_type')
                continue;

            if (strripos($key, 'pa_') !== false)
                continue;

            $tax_brands[$key] = $tax_val->labels->name;
        }


        $settings = array(

            array(
                'title' => '',
                'type' => 'title',
                'desc' => '',
            ),

            array(
                'title' => 'Назва магазину',
                'desc' => 'При вивантаженні',
                'id' => $id . '_title',
                'type' => 'text',
                'default' => get_option('blogname'),
                'desc_tip' => true,
            ),

            array(
                'title' => 'Компанія',
                'desc' => 'При вивантаженні',
                'id' => $id . '_desc',
                'type' => 'textarea',
                'default' => get_option('blogdescription'),
                'desc_tip' => true,
                'css' => 'width:500px; height:150px'
            ),
            array(
                'title' => 'Таксономія виробника',
                'id' => $id . '_vendors',
                'type' => 'select',
                'default' => 'false',
                'desc' => '',
                'desc_tip' => true,
                'options' => $tax_brands
            ),
            array(
                'title' => 'Глобальний виробник',
                'desc' => 'Якщо не зазначений виробник, буде це значення',
                'id' => $id . '_def_vendor',
                'type' => 'text',
                'default' => 'none',
                'desc_tip' => true,
            ),
            array(
                'title' => 'Вивантажувати властивості?',
                'desc' => '',
                'id' => $id . '_isexportattr',
                'type' => 'select',
                'default' => 'yes',
                'desc_tip' => true,
                'options' => array(
                    'no' => 'Ні',
                    'yes' => 'Так'
                )
            ),
            array(
                'title' => 'Вивантажувати додаткові зображення?',
                'desc' => '',
                'id' => $id . '_isexportpictures',
                'type' => 'select',
                'default' => 'yes',
                'desc_tip' => true,
                'options' => array(
                    'no' => 'Ні',
                    'yes' => 'Так'
                )
            ),
            array('type' => 'sectionend', 'id' => $this->id . '_head_options_export'),
            array(
                'title' => 'Управління ставками',
                'type' => 'title',
                'desc' => '',
            ),
            array(
                'title' => 'Управляти ставками?',
                'desc' => '',
                'id' => $id . '_isbid',
                'type' => 'select',
                'default' => 'no',
                'desc_tip' => true,
                'options' => array(
                    'no' => 'Ні',
                    'yes' => 'Так'
                )
            ),
            array(
                'title' => 'Ціна ставки (глобально на весь прайс)',
                'desc' => '13 == 0.13 уе',
                'id' => $id . '_bid',
                'type' => 'number',
                'default' => '13',
            ),


            array('type' => 'sectionend', 'id' => $this->id . '_head_options_deliver'),
            array(
                'title' => 'Налаштування доставки',
                'type' => 'title',
                'desc' => '',
            ),
            array(
                'title' => 'Можливість доставки товару',
                'desc' => '',
                'id' => $id . '_isdeliver',
                'type' => 'select',
                'default' => 'yes',
                'desc_tip' => true,
                'options' => array(
                    'no' => 'Ні',
                    'yes' => 'Так'
                )
            ),
            array(
                'title' => 'Глобальна ціна доставки',
                'desc' => '',
                'id' => $id . '_deliver_price',
                'type' => 'number',
                'default' => '300',
            ),
            array(
                'title' => 'Наявність самовивозу',
                'desc' => '',
                'id' => $id . '_ispickup',
                'type' => 'select',
                'default' => 'no',
                'desc_tip' => true,
                'options' => array(
                    'no' => 'Ні',
                    'yes' => 'Так'
                )
            ),
            array(
                'title' => 'Наявність точки продажу',
                'desc' => '',
                'id' => $id . '_isstore',
                'type' => 'select',
                'default' => 'no',
                'desc_tip' => true,
                'options' => array(
                    'no' => 'Ні',
                    'yes' => 'Так'
                )
            ),

            array('type' => 'sectionend', 'id' => $this->id . '_head_options_other'),
            array(
                'title' => 'Інші налаштування',
                'type' => 'title',
                'desc' => '',
            ),
            array(
                'title' => 'Купівля на маркеті',
                'desc' => '',
                'id' => $id . '_cpa',
                'type' => 'select',
                'default' => 'no',
                'desc_tip' => true,
                'options' => array(
                    'no' => 'Ні',
                    'yes' => 'Так'
                )
            ),
            array(
                'title' => 'Додавати атрибут group_id для варіацій',
                'desc' => 'Атрибут group_id необхідний, якщо Ваші товари відносяться до категорії "Одяг, взуття та аксесуари"',
                'id' => $id . '_isgroupidattr',
                'type' => 'select',
                'default' => 'no',
                'desc_tip' => true,
                'options' => array(
                    'no' => 'Ні',
                    'yes' => 'Так'
                )
            ),
            array(
                'title' => 'Замітка (sales_note)',
                'id' => $id . '_sales_note',
                'type' => 'textarea',
                'css' => 'width:500px; height:150px'
            ),
            array('type' => 'sectionend', 'id' => $this->id . '_head_options')

        ); // End pages settings


        if ($id != 'yandex_market')
            foreach ($settings as $key => $value) {
                if ($value['id'] == $id . '_cpa')
                    unset($settings[$key]);
            }

        $tax = get_taxonomies(array('object_type' => array('product')), 'objects');

        foreach ($tax as $key => $tax_val) {
            if ($key == 'product_type')
                continue;

            if (strripos($key, 'pa_') !== false)
                continue;

            $terms = get_terms($key);

            $options = array(
                'all' => 'Всі'
            );

            if (!empty($terms)) {
                foreach ($terms as $term) {
                    $options[$term->term_id] = $term->name;
                }
            }

            $settings[] = array(
                'title' => $tax_val->labels->name,
                'type' => 'title',
                'desc' => '',
            );

            $settings[] = array(
                'title' => 'Виберіть кілька елементів',
                'desc' => '',
                'id' => $id . '_tax_' . $key,
                'type' => 'multiselect',
                'default' => 'all',
                'css' => 'width:500px; min-height:200px; max-height:300px',
                'options' => $options
            );

            $settings[] = array('type' => 'sectionend', 'id' => $this->id . '_tax_' . $key . '_options');

        }

        $settings[] = array('type' => 'sectionend', 'id' => $this->id . '_options');

        $settings[] = array(
            'title' => 'Налаштування фільтрації',
            'type' => 'title',
        );


        $settings[] = array(
            'title' => 'Форма з фільтрами',
            'id' => $id . '_filters',
            'type' => 'filter_settings',
        );

        $settings[] = array('type' => 'sectionend', 'id' => $this->id . '_options_filter');


        return $settings;

    }
}

?>
<?php
if (!defined('WPINC')) {
    die;
}

function get_template_data() {
    return [
        [
            'id' => 1,
            'title' => 'Gardener Template',
            'image' => MTL_PLUGIN_URL . 'assets/images/template1.png',
            'demo_url' => 'https://gardener.iamabdus.com/v1-2/',
            'kit_url' => 'https://ocdi.iamabdus.com/kit-library/gardener-kit.zip',
            'type' => 'Business'
        ],
        [
            'id' => 2,
            // 'name' => 'Dentora Template',
            'title' => 'Dentora Template',
            'image' => MTL_PLUGIN_URL . 'assets/images/template2.png',
            // 'thumbnail' => MTL_PLUGIN_URL . 'assets/images/template2.png',
            'demo_url' => 'https://dentora.iamabdus.com/v1-4/',
            'kit_url' => 'https://ocdi.iamabdus.com/kit-library/digo-kit.zip',
            'type' => 'Medical'
        ]
    ];
}
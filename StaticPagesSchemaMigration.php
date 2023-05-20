<?php

/**
 * @file StaticPagesSchemaMigration.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StaticPagesSchemaMigration
 *
 * @brief Describe database table structures.
 */

namespace APP\plugins\generic\staticPages;

use APP\core\Application;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StaticPagesSchemaMigration extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // List of static pages for each context
        Schema::create('static_pages', function (Blueprint $table) {
            $table->bigInteger('static_page_id')->autoIncrement();
            $table->string('path', 255);
            $table->bigInteger('context_id');
            $table->foreign('context_id', 'static_pages_context_id')->references(Application::getContextDAO()->primaryKeyColumn)->on(Application::getContextDAO()->tableName)->onDelete('cascade');
        });

        // Static Page settings.
        Schema::create('static_page_settings', function (Blueprint $table) {
            $table->bigIncrements('static_page_setting_id');
            $table->bigInteger('static_page_id');
            $table->foreign('static_page_id', 'static_page_settings_static_page_id')->references('static_page_id')->on('static_pages')->onDelete('cascade');
            $table->index(['static_page_id'], 'static_page_settings_static_page_id');

            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->longText('setting_value')->nullable();
            $table->string('setting_type', 6)->comment('(bool|int|float|string|object)');
            $table->index(['static_page_id'], 'static_page_settings_static_page_id');
            $table->unique(['static_page_id', 'locale', 'setting_name'], 'static_page_settings_pkey');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::drop('static_page_settings');
        Schema::drop('static_pages');
    }
}

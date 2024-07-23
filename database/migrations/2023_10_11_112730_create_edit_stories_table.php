<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEditStoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('edit_stories', function (Blueprint $table) {
            $table->id();
            $table->string('story_id')->nullable();
            $table->string('package')->nullable();
            $table->string('user_id')->nullable();
            $table->string('question_ids')->nullable();
            $table->string('warmup_ids')->nullable();
            $table->longtext('storyitems1')->nullable();
            $table->longtext('storyitems2')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('edit_stories');
    }
}

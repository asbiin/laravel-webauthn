<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebauthn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('webauthn_keys', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('user_id')->unsigned();

            $table->string('name')->default('key');
            $table->string('credentialId', 255);
            $table->string('type', 255);
            $table->text('transports');
            $table->string('attestationType', 255);
            $table->text('trustPath');
            $table->text('aaguid');
            $table->text('credentialPublicKey');
            $table->integer('counter');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('credentialId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('webauthn');
    }
}

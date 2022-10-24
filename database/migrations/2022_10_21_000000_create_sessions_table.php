<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('sessions');
        Schema::create('sessions', function (Blueprint $table) {
            $table->uuid("uuid")->primary();
            $table->string("cognito_username")->nullable()->comment("Cognito上のユーザー名");
            $table->longText("id_token")->nullable()->comment("IDトークン");
            $table->longText("access_token")->nullable()->comment("アクセストークン");
            $table->longText("refresh_token")->nullable()->comment("リフレッシュトークン");
            $table->string("ip_address")->nullable()->comment("アクセス元IPアドレス");
            $table->longText("user_agent")->nullable()->comment("user agent");
            $table->timestamps();

            $table->foreign("cognito_username", "session_user_cognito_username")
                ->references("cognito_username")
                ->on("users")
                ->onUpdate("CASCADE")
                ->onDelete("CASCADE");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sessions');
    }
}

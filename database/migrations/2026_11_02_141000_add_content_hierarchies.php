<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', fn (Blueprint $table) => $table->foreignId('parent_id')->nullable()->constrained('pages')->restrictOnDelete());
        foreach (['pages', 'destinations'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedInteger('_lft')->default(0);
                $table->unsignedInteger('_rgt')->default(0);
                $table->index(['_lft', '_rgt', 'parent_id']);
            });
            $position = 0;
            $rows = DB::table($name)->orderBy('position')->orderBy('id')->get();
            $visit = function ($parent) use (&$visit, &$position, $rows, $name) {
                foreach ($rows->where('parent_id', $parent) as $row) {
                    $left = ++$position;
                    $visit($row->id);
                    DB::table($name)->where('id', $row->id)->update(['_lft' => $left, '_rgt' => ++$position]);
                }
            };
            $visit(null);
        }
    }

    public function down(): void
    {
        foreach (['pages', 'destinations'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropIndex(['_lft', '_rgt', 'parent_id']);
                $table->dropColumn(['_lft', '_rgt']);
            });
        }
        Schema::table('pages', fn (Blueprint $table) => $table->dropConstrainedForeignId('parent_id'));
    }
};

<?php

use yii\db\Migration;

/**
 * Class m200615_094445_add_homework_difficulty_level_to_student_school_table
 */
class m200615_094445_add_homework_difficulty_level_to_student_school_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // $this->addColumn('student_school', 'homework_difficulty_level', $this->string());
        $this->addColumn('student_school', 'homework_difficulty_level', $this->smallInteger());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('student_school', 'homework_difficulty_level');
    }
}

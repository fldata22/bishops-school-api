<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BishopsDataSeeder extends Seeder
{
    public function run(): void
    {
        // Wipe existing teachers, classes, and students (cascade handles dependents)
        Schema::disableForeignKeyConstraints();
        DB::table('attendance')->truncate();
        DB::table('sessions')->truncate();
        DB::table('teacher_module_assignments')->truncate();
        DB::table('students')->truncate();
        DB::table('classes')->truncate();
        DB::table('teachers')->truncate();
        Schema::enableForeignKeyConstraints();

        // Create teachers
        $joel = Teacher::create(['name' => 'Apostle Joel']);
        $richard = Teacher::create(['name' => 'Bishop Richard']);
        $nterful = Teacher::create(['name' => 'Bishop Nterful']);

        // Helper: create a class and add students
        $createClass = function (string $name, Teacher $teacher, string $category, array $students) {
            $class = SchoolClass::create([
                'name' => $name,
                'teacher_id' => $teacher->id,
                'category' => $category,
            ]);

            foreach ($students as $student) {
                Student::create([
                    'name' => $student['name'],
                    'class_id' => $class->id,
                    'country' => $student['country'] ?? null,
                ]);
            }
        };

        // ===== APOSTLE JOEL =====

        $createClass('APJ-UNITED CITIES', $joel, 'non_consecrated', [
            ['name' => 'Christopher Americano Niyonkunu', 'country' => 'Burundi'],
            ['name' => 'Jean De Dieu Ndikuriyo', 'country' => 'Burundi'],
            ['name' => 'Augustine Osei Amoako', 'country' => 'Guinea Bissau'],
            ['name' => 'Emmanuel Fosu Owusu', 'country' => 'Guinea Bissau'],
            ['name' => 'Mathew Haha', 'country' => 'Papua New Guinea'],
            ['name' => 'Eddie Kaidong Jnr', 'country' => 'Papua New Guinea'],
            ['name' => 'Sarah Bamete', 'country' => 'Papua New Guinea'],
            ['name' => 'Kristobella Haro', 'country' => 'Papua New Guinea'],
            ['name' => 'Andrew Arenade', 'country' => 'Papua New Guinea'],
            ['name' => 'Posolok Chukka', 'country' => 'Papua New Guinea'],
        ]);

        $createClass('APJ-UD AFRICA', $joel, 'non_consecrated', [
            ['name' => 'Kibita Anthony Agbesi', 'country' => 'Liberia'],
            ['name' => 'Arnold Ellis-Donkoh', 'country' => 'Liberia'],
            ['name' => 'Dave Brown', 'country' => 'Liberia'],
            ['name' => 'Dr. Ransford Darko', 'country' => 'Rwanda'],
            ['name' => 'Alexis Ruhumuriza', 'country' => 'Rwanda'],
            ['name' => 'Gabriel Obuobisa', 'country' => 'Rwanda'],
            ['name' => 'George Nesta Kwofie', 'country' => 'Uganda'],
            ['name' => 'Siisi Harrison', 'country' => 'Uganda'],
            ['name' => 'Alex Azazu', 'country' => 'Uganda'],
            ['name' => 'Jimmy Waiswa', 'country' => 'Uganda'],
        ]);

        $createClass('APJ-FIRST LOVE', $joel, 'non_consecrated', [
            ['name' => 'Ludwig Lamptey', 'country' => 'Congo'],
            ['name' => 'Maxine Mills', 'country' => 'Ghana'],
            ['name' => 'Hillary Agyeman', 'country' => 'Ghana'],
            ['name' => 'Priscilla Kayla Danquah', 'country' => 'Ghana'],
            ['name' => 'Joseph Mills', 'country' => 'Ghana'],
            ['name' => 'Jojo Stephens', 'country' => 'Ghana'],
            ['name' => 'Charles Djabatey', 'country' => 'Ghana'],
            ['name' => 'Reginald Sarkodie', 'country' => 'Madagascar'],
            ['name' => 'Jeremy Baiden', 'country' => 'USA'],
            ['name' => 'Gabriel Williams', 'country' => 'USA'],
            ['name' => 'Heywood Osei-Bonsu', 'country' => 'Netherlands'],
            ['name' => 'Solomon Moussa', 'country' => 'Sweden'],
        ]);

        $createClass('APJ-LOVE FIRST', $joel, 'non_consecrated', [
            ['name' => 'Bennet Morrison Obeng Darkwa', 'country' => 'Ghana'],
            ['name' => 'Daniel Fokuo', 'country' => 'Ghana'],
            ['name' => 'Lincoln Yorm', 'country' => 'Ghana'],
            ['name' => 'Ryan Emmanuel Aryee', 'country' => 'Ghana'],
            ['name' => 'Desmond de Youngster', 'country' => 'Ghana'],
            ['name' => 'Meshach Abbey', 'country' => 'Ghana'],
        ]);

        $createClass('APJ-UNITED ESCHATOS', $joel, 'newly_consecrated', [
            ['name' => 'Brian Bonnah', 'country' => 'American Samoa'],
            ['name' => 'Harold Bedu-Addo', 'country' => 'Chile'],
            ['name' => 'Kay Kwao', 'country' => 'Bangladesh'],
            ['name' => 'Aundrae Wood', 'country' => 'Jamaica'],
            ['name' => 'Charles Smith', 'country' => 'Costa Rica'],
            ['name' => 'Emmanuel John-Peters', 'country' => 'Taiwan'],
            ['name' => 'Stephen Sawyer', 'country' => 'Ghana'],
            ['name' => 'Francis Okyere', 'country' => 'Ghana'],
            ['name' => 'Esther Carina Okyere', 'country' => 'Ghana'],
            ['name' => 'Nelly Masuku', 'country' => 'Ghana'],
            ['name' => 'Nee Nunoo', 'country' => 'Seychelles'],
            ['name' => 'Norman Darkwa', 'country' => 'America'],
        ]);

        $createClass('APJ-FIRST LOVE (C)', $joel, 'newly_consecrated', [
            ['name' => 'Emmanuel Opoku (Tintin)', 'country' => 'Congo'],
            ['name' => 'Terry Bartels', 'country' => 'Ghana'],
            ['name' => 'Micheal Oddoye', 'country' => 'Ghana'],
            ['name' => 'Kojo Attemo', 'country' => 'Madagascar'],
            ['name' => 'Cleland Bruce', 'country' => 'Madagascar'],
            ['name' => 'Daniel Gyamerah', 'country' => 'Switzerland'],
            ['name' => 'Emanuel Fosu', 'country' => 'Switzerland'],
            ['name' => 'Andrew Gyamfi', 'country' => 'UK'],
            ['name' => 'Stephen Asamoah', 'country' => 'UK'],
            ['name' => 'King Adjei', 'country' => 'Belgium'],
            ['name' => 'Edwin Tutu', 'country' => 'Germany'],
            ['name' => 'Nana Kwame', 'country' => 'UK'],
            ['name' => 'Sena Agyepong', 'country' => 'UK'],
            ['name' => 'Allan Oyipo', 'country' => 'Brazil'],
        ]);

        // ===== BISHOP RICHARD =====

        $createClass('FIRST LOVE CARIBBEAN', $richard, 'non_consecrated', [
            ['name' => 'Stuart Sowah', 'country' => 'Martinique'],
            ['name' => 'Altobely Njoy', 'country' => 'Guadeloupe'],
            ['name' => 'Reginald Epson', 'country' => 'Dominica'],
            ['name' => 'Sam Bangura', 'country' => 'Grenada'],
            ['name' => 'Kayode Samuel Ewumi', 'country' => 'St - Lucia'],
            ['name' => 'Innocent Mojela', 'country' => 'Dominican Republic'],
            ['name' => 'Stanley Osei-Bonsu', 'country' => 'Dominican Republic'],
            ['name' => 'Esme Mantziba', 'country' => 'Belize'],
        ]);

        $createClass('BRA-UD AFRICA', $richard, 'newly_consecrated', [
            ['name' => 'Jeremy Menyisse', 'country' => 'Benin'],
            ['name' => 'Pascal Mensah', 'country' => 'Gabon'],
            ['name' => 'Paul Mbah', 'country' => 'Malawi'],
            ['name' => 'Peter Okereke', 'country' => 'Nigeria'],
            ['name' => 'Ade Adepoju', 'country' => 'Nigeria'],
            ['name' => 'Otonye Alba Ekefre', 'country' => 'Nigeria'],
            ['name' => 'Felix Bediako', 'country' => 'Nigeria'],
            ['name' => 'Dayo Adejini', 'country' => 'Nigeria'],
            ['name' => 'Tennyson Ojeisekhoba', 'country' => 'Nigeria'],
            ['name' => 'Martin Luther Moro', 'country' => 'Nigeria'],
            ['name' => 'Jason Kofi-Opata', 'country' => 'Nigeria'],
            ['name' => 'Joseph Aryee', 'country' => 'Swaziland'],
            ['name' => 'Michael Asare', 'country' => 'Tanzania'],
        ]);

        // ===== BISHOP NTERFUL =====

        $createClass('BN-UNITED CITIES', $nterful, 'non_consecrated', [
            ['name' => 'Espour Kengbo', 'country' => 'Burkina Faso'],
            ['name' => 'Hugues Samandoulougou', 'country' => 'Burkina Faso'],
            ['name' => 'Josue De', 'country' => 'Burkina Faso'],
            ['name' => 'Miguel Lao', 'country' => 'Burkina Faso'],
            ['name' => 'Obed Charway', 'country' => 'Chad'],
            ['name' => 'Andrews Anyomi', 'country' => 'Niger'],
            ['name' => 'Joseph Gondwe', 'country' => 'Malawi'],
            ['name' => 'Harry Dartey', 'country' => 'Swaziland'],
            ['name' => 'Patrick Akese', 'country' => 'Gabon'],
            ['name' => 'Andrade Caetano', 'country' => 'Sao Tome'],
        ]);

        $createClass('UNITED JESUS', $nterful, 'non_consecrated', [
            ['name' => 'Alex Bodua'],
            ['name' => 'Daniel Kharis Boateng'],
            ['name' => 'Jerry John Kwame Mensah'],
            ['name' => 'David Bilikuni'],
            ['name' => 'Geoffrey Nassama'],
            ['name' => 'Samuel Akparibo'],
            ['name' => 'Michael Agyemang'],
            ['name' => 'Ben Yusuf'],
            ['name' => 'Kennedy Boamah'],
            ['name' => 'Kwesi Sam'],
            ['name' => 'Paul Baba'],
            ['name' => 'Robert Awuni'],
            ['name' => 'Richard Ampadu'],
            ['name' => 'Wisdom Ahiaku'],
            ['name' => 'Eli Ayiglo'],
        ]);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('StudentNumber')->nullable();
            $table->string('Sex')->nullable();
            $table->string('LastName')->nullable();
            $table->string('FirstName')->nullable();
            $table->string('MiddleName')->nullable();
            $table->string('Initials')->nullable();
            $table->date('DateOfBirth')->nullable();
            $table->string('Photo')->nullable();
            $table->string('Signature')->nullable();
            $table->string('Address')->nullable();
            $table->string('City')->nullable();
            $table->string('StateProvince')->nullable();
            $table->string('PostalCode')->nullable();
            $table->string('Country')->nullable();
            $table->string('HomePhone')->nullable();
            $table->string('MobilePhone')->nullable();
            $table->string('EmailAddress')->nullable();
            $table->string('EmergencyContactName')->nullable();
            $table->string('EmergencyContactPhone')->nullable();
            $table->string('UniversityName')->nullable();
            $table->string('CollegeName')->nullable();
            $table->string('Department')->nullable();
            $table->string('Major')->nullable();
            $table->string('Year')->nullable();
            $table->string('Class')->nullable();
            $table->string('SocialSecurityNumber')->nullable();
            $table->text('Notes')->nullable();
            $table->text('SpecialCircumstances')->nullable();
            $table->string('PhysicianName')->nullable();
            $table->string('PhysicianPhoneNumber')->nullable();
            $table->string('Allergies')->nullable();
            $table->string('Medications')->nullable();
            $table->string('InsuranceCarrier')->nullable();
            $table->string('InsuranceNumber')->nullable();
            $table->boolean('ActiveStudent')->nullable();
            $table->date('ActivationDate')->nullable();
            $table->date('ExpirationDate')->nullable();
            $table->date('PrintDate')->nullable();
            $table->integer('PrintCount')->nullable();
            $table->date('ModifiedDate')->nullable();
            $table->string('SystemOperator')->nullable();
            $table->string('CardSerialNumber')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'StudentNumber',
                'Sex',
                'LastName',
                'FirstName',
                'MiddleName',
                'Initials',
                'DateOfBirth',
                'Photo',
                'Signature',
                'Address',
                'City',
                'StateProvince',
                'PostalCode',
                'Country',
                'HomePhone',
                'MobilePhone',
                'EmailAddress',
                'EmergencyContactName',
                'EmergencyContactPhone',
                'UniversityName',
                'CollegeName',
                'Department',
                'Major',
                'Year',
                'Class',
                'SocialSecurityNumber',
                'Notes',
                'SpecialCircumstances',
                'PhysicianName',
                'PhysicianPhoneNumber',
                'Allergies',
                'Medications',
                'InsuranceCarrier',
                'InsuranceNumber',
                'ActiveStudent',
                'ActivationDate',
                'ExpirationDate',
                'PrintDate',
                'PrintCount',
                'ModifiedDate',
                'SystemOperator',
                'CardSerialNumber'
            ]);
        });
    }
};

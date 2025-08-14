<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\UploadedFile;
use Filament\Forms\Get;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Log;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Account Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')->required(),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->default('12345') // Works for create
                            ->afterStateHydrated(function ($component, $record) {
                                // Force field to show "12345" in edit mode
                                if ($record) {
                                    $component->state('12345');
                                }
                            })
                            ->dehydrateStateUsing(function ($state, $record) {
                                // Keep old password if value is unchanged
                                if ($record && $state === '12345') {
                                    return $record->password;
                                }
                                return bcrypt($state);
                            })
                            ->visible(false)
                            ->required(),
                        Forms\Components\Select::make('role')
                            ->options([
                                'admin' => 'Admin',
                                'student' => 'Student',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('StudentNumber'),
                        Forms\Components\Select::make('Sex')->options([
                            'Male' => 'Male',
                            'Female' => 'Female',
                        ]),
                    ])->columns(3),

                Forms\Components\Section::make('Personal Details')
                    ->schema([
                        Forms\Components\TextInput::make('LastName'),
                        Forms\Components\TextInput::make('FirstName'),
                        Forms\Components\TextInput::make('MiddleName'),
                        Forms\Components\TextInput::make('Initials'),
                        Forms\Components\DatePicker::make('DateOfBirth'),
                        Forms\Components\FileUpload::make('Photo')
                            ->image()
                            ->disk('public') // store in storage/app/public
                            ->directory('photos') // storage/app/public/photos
                            ->preserveFilenames(false)
                            ->storeFileNamesIn('Photo')
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, $get) {
                                $studentNumber = (string) $get('StudentNumber');
                                $filename = $studentNumber . '.' . $file->getClientOriginalExtension();
                                $file->storeAs('photos', $filename, 'public'); // photos inside storage/app/public/photos
                                return $filename; // only filename stored in DB
                            }),
                        Forms\Components\FileUpload::make('Signature')->image(),
                        Forms\Components\TextInput::make('Address'),
                        Forms\Components\TextInput::make('City'),
                        Forms\Components\TextInput::make('StateProvince'),
                        Forms\Components\TextInput::make('PostalCode'),
                        Forms\Components\TextInput::make('Country'),
                        Forms\Components\TextInput::make('HomePhone'),
                        Forms\Components\TextInput::make('MobilePhone'),
                        Forms\Components\TextInput::make('EmailAddress'),
                        Forms\Components\TextInput::make('EmergencyContactName'),
                        Forms\Components\TextInput::make('EmergencyContactPhone'),
                    ])->columns(3),

                Forms\Components\Section::make('Education Details')
                    ->schema([
                        Forms\Components\TextInput::make('UniversityName'),
                        Forms\Components\TextInput::make('CollegeName'),
                        Forms\Components\TextInput::make('Department'),
                        Forms\Components\TextInput::make('Major'),
                        Forms\Components\TextInput::make('Year'),
                        Forms\Components\TextInput::make('Class'),
                    ])->columns(3),

                Forms\Components\Section::make('Other Info')
                    ->schema([
                        Forms\Components\TextInput::make('SocialSecurityNumber'),
                        Forms\Components\Textarea::make('Notes'),
                        Forms\Components\Textarea::make('SpecialCircumstances'),
                        Forms\Components\TextInput::make('PhysicianName'),
                        Forms\Components\TextInput::make('PhysicianPhoneNumber'),
                        Forms\Components\TextInput::make('Allergies'),
                        Forms\Components\TextInput::make('Medications'),
                        Forms\Components\TextInput::make('InsuranceCarrier'),
                        Forms\Components\TextInput::make('InsuranceNumber'),
                        Forms\Components\Toggle::make('ActiveStudent'),
                        Forms\Components\DatePicker::make('ActivationDate'),
                        Forms\Components\DatePicker::make('ExpirationDate'),
                        Forms\Components\DatePicker::make('PrintDate'),
                        Forms\Components\TextInput::make('PrintCount')->numeric(),
                        Forms\Components\DateTimePicker::make('ModifiedDate'),
                        Forms\Components\TextInput::make('SystemOperator'),
                        Forms\Components\TextInput::make('CardSerialNumber'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([ // this places the button before the search bar
                Tables\Actions\Action::make('importCsv')
                    ->label('Import CSV')
                    ->button()
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        Forms\Components\FileUpload::make('csv_file')
                            ->label('Upload CSV File')
                            ->required()
                            ->acceptedFileTypes(['text/csv', 'text/plain'])
                            ->storeFiles(false), // don't store permanently, just process
                    ])
                    ->action(function (array $data) {
                        ini_set('max_execution_time', 0); // unlimited execution time
                        set_time_limit(0); // just to be sure
                        // Read the uploaded CSV
                        /** @var TemporaryUploadedFile $file */
                        $file = $data['csv_file'];
                        $path = $file->getRealPath();

                        if (($handle = fopen($path, 'r')) !== false) {
                            $header = null;
                            $count = 0;

                            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                                if (!$header) {
                                    $header = $row; // first row as header
                                    continue;
                                }

                                $rowData = array_combine($header, $row);

                                // Example mapping
                                try {
                                    // Step 1: Create account with only name, email, and password
                                    $user = User::updateOrCreate(
                                        // Search conditions
                                        ['StudentNumber' => $rowData['StudentNumber']],

                                        // Values to update or insert
                                        [
                                            'name' => trim(($rowData['FirstName'] ?? '') . ' ' . ($rowData['LastName'] ?? '')),
                                            'email' => strtolower(
                                                str_replace(' ', '', ($rowData['FirstName'] ?? '') . '.' . ($rowData['LastName'] ?? '')) . '@vci.com'
                                            ),
                                            'password' => '12345', // default password
                                        ]
                                    );
                                    // $user = User::create([
                                    //     'name' => trim(($rowData['FirstName'] ?? '') . ' ' . ($rowData['LastName'] ?? '')),
                                    //     'email' => strtolower(
                                    //         str_replace(' ', '', ($rowData['FirstName'] ?? '') . '.' . ($rowData['LastName'] ?? '')) . '@vci.com'
                                    //     ),
                                    //     'password' => '12345', // default password
                                    // ]);
            
                                    // Step 2: Update other fields individually
                                    try {
                                        $user->role = 'student';
                                        $user->save();
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert role for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['StudentNumber'])) {
                                            $user->StudentNumber = $rowData['StudentNumber'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert StudentNumber for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['Sex'])) {
                                            $user->Sex = $rowData['Sex'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert Sex for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['LastName'])) {
                                            $user->LastName = $rowData['LastName'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert LastName for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['FirstName'])) {
                                            $user->FirstName = $rowData['FirstName'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert FirstName for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['MiddleName'])) {
                                            $user->MiddleName = $rowData['MiddleName'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert MiddleName for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['Initials'])) {
                                            $user->Initials = $rowData['Initials'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert Initials for user {$user->id}: " . $e->getMessage());
                                    }
                                    // try {
                                    //     if (!empty($rowData['DateOfBirth'])) {
                                    //         $user->DateOfBirth = $rowData['DateOfBirth'];
                                    //         $user->save();
                                    //     }
                                    // } catch (\Throwable $e) {
                                    //     Log::warning("Failed to insert DateOfBirth for user {$user->id}: " . $e->getMessage());
                                    // }
                                    try {
                                        if (!empty($rowData['Photo'])) {
                                            $user->Photo = $rowData['Photo'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert Photo for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['Signature'])) {
                                            $user->Signature = $rowData['Signature'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert Signature for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['Address'])) {
                                            $user->Address = $rowData['Address'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert Address for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['City'])) {
                                            $user->City = $rowData['City'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert City for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['StateProvince'])) {
                                            $user->StateProvince = $rowData['StateProvince'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert StateProvince for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['PostalCode'])) {
                                            $user->PostalCode = $rowData['PostalCode'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert PostalCode for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['Country'])) {
                                            $user->Country = $rowData['Country'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert Country for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['HomePhone'])) {
                                            $user->HomePhone = $rowData['HomePhone'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert HomePhone for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['MobilePhone'])) {
                                            $user->MobilePhone = $rowData['MobilePhone'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert MobilePhone for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['EmailAddress'])) {
                                            $user->EmailAddress = $rowData['EmailAddress'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert EmailAddress for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['EmergencyContactName'])) {
                                            $user->EmergencyContactName = $rowData['EmergencyContactName'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert EmergencyContactName for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['EmergencyContactPhone'])) {
                                            $user->EmergencyContactPhone = $rowData['EmergencyContactPhone'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert EmergencyContactPhone for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['UniversityName'])) {
                                            $user->UniversityName = $rowData['UniversityName'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert UniversityName for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['CollegeName'])) {
                                            $user->CollegeName = $rowData['CollegeName'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert CollegeName for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['Department'])) {
                                            $user->Department = $rowData['Department'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert Department for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['Major'])) {
                                            $user->Major = $rowData['Major'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert Major for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['Year'])) {
                                            $user->Year = $rowData['Year'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert Year for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['Class'])) {
                                            $user->Class = $rowData['Class'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert Class for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['SocialSecurityNumber'])) {
                                            $user->SocialSecurityNumber = $rowData['SocialSecurityNumber'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert SocialSecurityNumber for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['Notes'])) {
                                            $user->Notes = $rowData['Notes'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert Notes for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['SpecialCircumstances'])) {
                                            $user->SpecialCircumstances = $rowData['SpecialCircumstances'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert SpecialCircumstances for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['PhysicianName'])) {
                                            $user->PhysicianName = $rowData['PhysicianName'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert PhysicianName for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['PhysicianPhoneNumber'])) {
                                            $user->PhysicianPhoneNumber = $rowData['PhysicianPhoneNumber'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert PhysicianPhoneNumber for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['Allergies'])) {
                                            $user->Allergies = $rowData['Allergies'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert Allergies for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['Medications'])) {
                                            $user->Medications = $rowData['Medications'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert Medications for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['InsuranceCarrier'])) {
                                            $user->InsuranceCarrier = $rowData['InsuranceCarrier'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert InsuranceCarrier for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['InsuranceNumber'])) {
                                            $user->InsuranceNumber = $rowData['InsuranceNumber'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert InsuranceNumber for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['ActiveStudent'])) {
                                            $user->ActiveStudent = $rowData['ActiveStudent'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert ActiveStudent for user {$user->id}: " . $e->getMessage());
                                    }
                                    // try {
                                    //     if (!empty($rowData['ActivationDate'])) {
                                    //         $user->ActivationDate = $rowData['ActivationDate'];
                                    //         $user->save();
                                    //     }
                                    // } catch (\Throwable $e) {
                                    //     Log::warning("Failed to insert ActivationDate for user {$user->id}: " . $e->getMessage());
                                    // }
                                    // try {
                                    //     $timestamp = strtotime($rowData['ExpirationDate']);
                                    //     $user->ExpirationDate = $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
                                    //     $user->save();
                                    // } catch (\Throwable $e) {
                                    //     Log::warning("Failed to insert ExpirationDate for user {$user->id}: " . $e->getMessage());
                                    // }
                                    // try {
                                    //     if (!empty($rowData['PrintDate'])) {
                                    //         $timestamp = strtotime($rowData['PrintDate']);
                                    //         $user->PrintDate = $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
                                    //         $user->save();
                                    //     }
                                    // } catch (\Throwable $e) {
                                    //     Log::warning("Failed to insert PrintDate for user {$user->id}: " . $e->getMessage());
                                    // }
                                    try {
                                        if (!empty($rowData['PrintCount'])) {
                                            $user->PrintCount = $rowData['PrintCount'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert PrintCount for user {$user->id}: " . $e->getMessage());
                                    }
                                    // try {
                                    //     if (!empty($rowData['ModifiedDate'])) {
                                    //         $user->ModifiedDate = $rowData['ModifiedDate'];
                                    //         $user->save();
                                    //     }
                                    // } catch (\Throwable $e) {
                                    //     Log::warning("Failed to insert ModifiedDate for user {$user->id}: " . $e->getMessage());
                                    // }
                                    try {
                                        if (!empty($rowData['SystemOperator'])) {
                                            $user->SystemOperator = $rowData['SystemOperator'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert SystemOperator for user {$user->id}: " . $e->getMessage());
                                    }
                                    try {
                                        if (!empty($rowData['CardSerialNumber'])) {
                                            $user->CardSerialNumber = $rowData['CardSerialNumber'];
                                            $user->save();
                                        }
                                    } catch (\Throwable $e) {
                                        Log::warning("Failed to insert CardSerialNumber for user {$user->id}: " . $e->getMessage());
                                    }


                                } catch (\Throwable $e) {
                                    Log::error("Failed to create user for row: " . json_encode($rowData) . " | Error: " . $e->getMessage());
                                    continue; // Skip to next row if user creation fails
                                }

                                $count++;
                                Log::info($count);
                            }
                            fclose($handle);

                            Notification::make()
                                ->title("Imported {$count} users successfully!")
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\BadgeColumn::make('role')
                    ->colors([
                        'primary' => 'admin',
                        'success' => 'student',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'student' => 'Student',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', 'student'); // Only show students
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-user-group';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

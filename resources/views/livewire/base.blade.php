<div class="w-full min-h-screen bg-gradient-to-br from-[#e0f7fa] via-[#f5f5f5] to-[#d3f2ed] flex flex-col">

    <!-- Header -->
    <div
        class="bbtmarites-font bg-[#2f9e89] text-white flex items-center justify-center gap-3 py-4 text-5xl tracking-wide shadow-md rounded-b-2xl mb-2">
        <img src="{{ asset('logos/vci.png') }}" alt="VCI Logo" class="h-16 w-16 object-contain">
        VALENCIA COLLEGES (BUKIDNON), INC
    </div>

    <div class="grid grid-cols-12 flex-1 gap-6 px-8 py-6">
        <!-- Left: Main Image & QR -->
        <div class="col-span-4 flex flex-col items-center justify-center h-full">
            <div class="bg-white rounded-2xl shadow-lg p-6 flex flex-col items-center w-full">
                @php
                $photoUrl = null;
                try {
                $photoUrl = asset('photos/' . $student->StudentNumber . '.png');
                } catch (\Throwable $th) {
                $photoUrl = asset('photos/avatar.png');
                }
                @endphp

                <img src="{{ $photoUrl }}" alt="{{ $student->name ?? " student photo" }}"
                    class="w-[420px] h-[420px] object-cover rounded-xl border-4 border-[#2f9e89] mb-4">
                <div class="w-full">
                    <label for="qr-input" class="block text-[18px] font-semibold text-[#2f9e89] mb-2">Scan QR
                        Code</label>
                    <input id="qr-input" type="text"
                        class="border-2 border-[#2f9e89] rounded-lg px-4 py-3 w-full text-center text-[20px] bg-white shadow focus:outline-none focus:ring-2 focus:ring-[#2f9e89] transition-all duration-150"
                        placeholder="Place cursor here and scan..." autofocus />

                    <script>
                        document.addEventListener('livewire:init', function () {
                            let timeout = null;
                            const input = document.getElementById('qr-input');

                            input.addEventListener('input', function () {
                                clearTimeout(timeout);
                                timeout = setTimeout(() => {
                                    if (input.value.trim() !== '') {
                                        const action = "{{ request()->routeIs('attendance.in') ? 'in' : 'out' }}";

                                        Livewire.dispatch('qrScanned', { value: input.value.trim(), action: action });
                                        input.value = ''; // Clear after submit
                                    }
                                }, 500);
                            });
                        });
                    </script>
                </div>
            </div>
        </div>

        <!-- Center: Info -->
        <div class="col-span-6 flex flex-col items-center justify-center h-full">
            <div class="bg-white rounded-2xl shadow-lg p-8 w-full flex flex-col items-center">
                <!-- Student Info -->
                @if($student)
                <p class="text-[22px] text-[#2f9e89] font-semibold mb-2 tracking-wide">
                    {{ $student->StudentNumber }}
                </p>
                <p class="text-[32px] font-extrabold text-gray-800 mb-1">
                    {{ strtoupper($student->FirstName . ' ' . $student->MiddleName . ' ' . $student->LastName) }}
                </p>
                <p class="text-[18px] text-gray-600 mb-2">{{ $student->Department }}</p>
                <p class="text-[15px] text-gray-500 mb-4">
                    Today is {{ now()->format('l, F d, Y') }}
                </p>

                <div class="grid grid-cols-2 gap-6 w-full mt-4">
                    <div
                        class="bg-[#2f9e89] rounded-xl shadow text-white text-center font-bold text-[20px] py-4 flex flex-col items-center">
                        <span class="text-[16px] mb-2">IN</span>
                        <span class="text-[24px]">
                            {{ $attendance?->time_in ? \Carbon\Carbon::parse($attendance->time_in)->format('h:i A') :
                            '--' }}
                        </span>
                    </div>
                    <div
                        class="bg-[#2f9e89] rounded-xl shadow text-white text-center font-bold text-[20px] py-4 flex flex-col items-center">
                        <span class="text-[16px] mb-2">OUT</span>
                        <span class="text-[24px]">
                            {{ $attendance?->time_out ? \Carbon\Carbon::parse($attendance->time_out)->format('h:i A') :
                            '--' }}
                        </span>
                    </div>
                </div>
                @else
                <p class="text-gray-500">Scan a QR code to see details...</p>
                @endif
            </div>
        </div>

        <!-- Right: Side People -->
        <div class="col-span-2 flex flex-col items-center justify-center h-full">
            <div class="bg-white rounded-2xl shadow-lg p-6 w-full flex flex-col items-center space-y-8">
                @foreach($recent as $record)
                <div class="flex flex-col items-center">
                    <span class="bg-[#2f9e89] text-white text-[14px] mb-2 px-3 py-0.5 mt-2 rounded-full shadow">
                        {{ $record->time_out ? 'OUT' : 'IN' }}
                    </span>
                    <img src="{{ asset('photos/'.$record->student_id.'png') ?? asset('photos/avatar.png') }}"
                        alt="{{ $record->user->name }}"
                        class="w-[220px] h-[240px] object-cover rounded-xl border-2 border-[#2f9e89]">
                    <p class="text-[15px] font-semibold text-gray-700 mt-1">
                        {{ $record->user->FirstName }} {{ $record->user->LastName }}
                    </p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
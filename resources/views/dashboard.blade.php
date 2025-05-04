<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h1 class="mb-4">{{ __("Opciones") }}</h1>
                    <div class="grid gap-2 ">
                        <div x-data="testConnection()">
                            <button type="button" @click="testConnectionButton()" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-100 rounded hover:bg-gray-400 dark:hover:bg-gray-500">
                                Prueba de conexion
                            </button> 
                            <div x-show="status" class="mt-2" x-text="status" :class="success ? 'text-green-600' : 'text-red-600'"></div>   
                        </div>
                        <div x-data="getInfoEP()">
                        <button type="button" @click="getInfoEPButton()" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-100 rounded hover:bg-gray-400 dark:hover:bg-gray-500">
                                Obtener informacion de cartas
                            </button> 
                            <div x-show="status" class="mt-2" x-text="status" :class="success ? 'text-green-600' : 'text-red-600'"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
<script>
    function testConnection() {
        return {
            status: '',
            success: false,
            testConnectionButton() {
                fetch('/test-connection', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({})
                })
                .then(res => res.json())
                .then(data => {
                    this.success = data.success;
                    this.status = data.message;
                })
                .catch(err => {
                    this.success = false;
                    this.status = 'Error de red';
                });
            }
        }
    }

    function getInfoEP(){
        return {
            status: '',
            success: false,
            getInfoEPButton() {
                fetch('/email-pdf-letters', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({})
                })
                .then(res => res.json())
                .then(data => {
                    this.success = data.success;
                    this.status = data.message;
                })
                .catch(err => {
                    this.success = false;
                    this.status = 'Error de red';
                });
            }
        }
    }
</script>
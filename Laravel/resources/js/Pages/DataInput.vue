<template>
    <app-layout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Data Input
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                   <div>Input box for excel format</div>
                   <div>Direct, unvalidated json input</div>
                   <textarea type="text" placeholder="json here" v-model="directjson"/>
                   <button v-on:click="submitJSON">Submit JSON</button>
                   <div>API Response:</div>
                   <div>{{apiResponseCode}} ... {{apiResponseBody}}</div>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>
    import AppLayout from './../Layouts/AppLayout'
    export default {
        components: {
            AppLayout,
        },
        data() {
            return {
                directjson: '',
                csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                apiResponseCode:'',
                apiResponseBody: '',
            };
        },
        methods: {
            submitJSON(){
                var vm = this;
                console.log(vm.directjson);
                //format the data and send it to the api 
                fetch('http://localhost:8000/api/datasets', {
                    method: 'POST',
                    headers: {
                        'Content-Type':'application/json',
                        'X-CSRF-TOKEN':vm.csrf,
                        },
                    body: vm.directjson,
                }).then(async res => {
                    vm.apiResponseCode = res.status;
                    const body = await res.text();
                    vm.apiResponseBody = body;
                    return body;
                })
                .then(data=>{
                    console.log('data:',data);
                    })
                .catch(error=>console.log(error));
            }
        }
    }
</script>

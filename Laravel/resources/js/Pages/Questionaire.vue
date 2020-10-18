<template>
    <app-layout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Questionaire
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="datasets">
                        <div v-if="datasetsLoading" class="loading">
                        Loading...
                        </div>

                        <div v-if="datasetsError" class="error">
                        {{ datasetsError }}
                        </div>

                        <div v-if="datasets" class="content">
                            <ul id="categorylist">
                                <li v-for="category in categories" :key="category.name">
                                    Category: {{category.name}}
                                        <ul v-bind:id="category.name+'datasets'">
                                            <li v-for="dataset in category.datasets" :key="dataset.id">
                                                What {{dataset.long_name}} would you prefer your country to have?
                                                <div>Choose weight:</div>
                                                <input type="range" min="0" max="100" step="1"/>
                                                <div>Choose ideal value:</div>
                                                <input type="range"
                                                    @change="changeSlider" 
                                                    v-bind:min="dataset.min_value" 
                                                    v-bind:max="dataset.max_value" 
                                                    v-bind:step="(dataset.max_value-dataset.min_value)/100"
                                                />
                                                <div>Choose how to handle missing data:</div>
                                                <div>Special parameters for missing data handling:</div>
                                            </li>
                                        </ul>
                                </li>
                            </ul>
                        </div>
                    </div>
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
                csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                datasetsLoading: false,
                datasetsError: null,
                datasets: null,
                categories: null,
            };
        },
        created(){
            this.getDatasets()
        },
        methods: {
            getDatasets(){
                var vm = this;
                vm.datasetsError = vm.datasets = null;
                vm.datasetsLoading = true;
                //format the data and send it to the api
                fetch('http://localhost:8000/api/datasets', {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN':vm.csrf,
                        },
                })
                .then(res =>{
                    vm.datasetsLoading = false;
                    console.log(res);
                    return res.json();
                    })
                .then(data => {
                    console.log(data);
                    vm.datasets = data;
                    //distribute into categories
                    let categories = [];
                    let categoryNames = [];
                    data.forEach(dataset=>{
                        if (!categoryNames.includes(dataset['category'])){
                            categories.push({
                                name:dataset['category'],
                                datasets: [dataset],
                            });
                            categoryNames.push(dataset['category']);
                        } else {
                            const index = categories.findIndex(element => element.name===dataset['category']);
                            categories[index]['datasets'].push(dataset);
                        }
                    });
                    vm.categories = categories;
                    return;
                })
                .catch(error => vm.datasetsError = error);
            }
        }
    }
</script>

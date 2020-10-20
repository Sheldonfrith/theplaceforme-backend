<x-guest>
        <h1 class="text-5xl">The Place For Me API Documentation</h1>
        <div>
        <h2 class="text-2xl">Endpoints</h2>
        <p>* All API endpoints start with: 'https://theplacefor.me/api'</p>
        <div>
        <h3 class="text-xl">'/datasets'</h3>
            <div>
            <h4>
                GET
            </h4>
                Returns the following fields for all existing datasets in the database:
                    'id', 'updated_at', 'long_name', 'data_type', 'max_value', 'min_value', 'source_link', 'source_description', 'unit_description', 'notes', 'category'.
                
                Response Body Format: 
                    JSON array, with JSON objects as elements. Each element object contains the field names listed above as keys, and the field's corresponding value for the dataset as values.

            </div>
            <div>
            <h4>
                POST
            </h4>
                Add a new dataset to the database
                Send the dataset as JSON in the body of the request (request type application/json)
                JSON format schema:
                {'meta':{
                    'long_name': 'required|string',
                    'data_type': 'required|string|',
                    'country_id_type': 'required|string',
                    'unit_description': 'required|string',
                    'notes': 'optional|nullable|string',
                    'category': 'required|string',
                    'source_link': 'required if source_description doesnt exist | valid url',
                    'source_description': 'required if source_link doesnt exist | string',

                },'countryName':'datatype corresponding to 'data_type' in 'meta' object','countryName2':'', etc...}

The JSON must include ALL countries (get them from /countries GET endpoint);
If the country doesn't have any data set its value to JSON's null type.
Currently supported 'data_type' values: 'float','double','integer' ('boolean' coming soon)
'country_id_type' correspond to the fields returned by the /countries GET endpoint:
'alpha_three_code', 'alpha_two_code', 'numeric_code','primary_name'
COMING SOON: *Synonym guessing is only supported for datasets using the 'primary_name' id type 
'long_name': a string describing the dataset in plain english, to tell if it is a good long_name
this sentence should make sense: "What _Long_Name_Here_ would you prefer your country to have?"
'unit_description': precise and complete description of what the datasets values are describing
for example 'number of people per square kilometre' with numeric data values, or 'country is a monarchy?' for boolean values.
'notes': any additional information about the dataset that people should know, for example if
the source-link dataset's missing values have been filled in, indicate how they were filled in
'category': must be one of the categories listed in the /categories GET endpoint
'demographics', 'geography', 'violence', 'religion', 'government', 'economics', 'immigration', 'culture', 'health', 'environment', 'travel', 'education', 'housing', 'technology', 'uncategorized',
'source_link': must be a valid url, link to where the dataset data came from
'source_description': if dataset was not pulled mostly from a specific url describe where the dataset came from here
            </div>
        </div>
        <div>
        <h3 class="text-xl">'/countries'</h3>
            <div>
            <h4>
                GET
            </h4>
            Returns a JSON list of objects, each object corresponding to a country.
            Each country object includes the following fields with the corresponding values
            for that country:   
            'id'
            'updated_at' => timestamp
            'alpha_three_code' => string, 3 characters
            'alpha_two_code' => string, 2 characters
            'numeric_code' => number
            'primary_name' => string full english name of country (most popular variant)
            </div>
        </div>
        <div>
        <h3 class="text-xl">'/scores'</h3>
            <div>
            <h4>
                POST
            </h4>
            Send this endpoint your dataset value preferences and it will respond with the scores and rankings
            for all countries based on the values you inputted
            Send your data as JSON in the body of the request with the following format:
            An array containing object elements, each object element corresponds to a valid dataset in the database
            Each dataset object has the following form:
{
    'id': required |integer | the id of the dataset
    'weight': required | integer | the weight this dataset should be given in the final ranking
        if this is set to zero the dataset will be ignored
    'idealValue': currently(required | number) future( required if customScoreFunction null | number or boolean) the value (within the min_value and max_value range for the dataset's data)
        that you prefer most, countries will be ranked by how closely their value for this dataset
        corresponds to the idealValue you set
    'customScoreFunction': not supported yet, don't include or set to null. Eventually you can input a custom function that 
    accepts a single input which is the dataset value for a single country, your function should use that 
    value to calculate a score for the country that is between the min and max possible scores (0-10000 currently)
    'missingDataHandlerMethod': required | string | must be one of the methods listed by the 
    /missing-data-handler-methods GET endpoint, tells the server how to calculate scores for countries
    with null data on a per-dataset basis
    'missingDataHandlerInput': required only if the chosen 'missingDataHandlerMethod' requires it,
    used to allow more complex missingDataHandlerMethods
}
                The server will calculate the results and send back a response with a JSON body in this form:
                object with alpha_three_codes for all countries as keys, values are objects with the following form:
{
'primary_name': the full readable name of the country, string
'totalScore': the total score of the country, integer, minimum score is 0, max is 10000 x the number of datasets with weights >0
'rank': the rank of this country relative to all other country's. rank 1 being the closest match to the input data given, etc.
'scoreBreakdown': {
    an object with the following form:
    'datasetID': score for that dataset, integer, min 0 max 10000
    only includes datasets with weights greater than 0
}
}
            </div>
        </div>
        <div>
        <h3 class="text-xl">'/missing-data-handler-methods'</h3>
            <div>
            <h4>
                GET
            </h4>
            </div>
        </div>
        <div>
        <h3 class="text-xl">'/categories'</h3>
            <div>
            <h4>
                GET
            </h4>
            </div>
        </div>
        </div>  
</x-guest>
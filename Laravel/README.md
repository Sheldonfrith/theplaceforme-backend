# The Place For Me - Backend

This is primarily an API for serving standardized country datasets.
... no, the datasets should be hidden, as well as the calculations (for IP purposes)

So instead the API will output meta info about the datasets it has access to, 
the consuming client can then do whatever they want with that meta info (probably use
it to structure a questionaire form/input). 
- a list of the meta-info for each dataset:
    -name, data type, units, source link, methodology
    - data complete-ness
    -suggested dataset category (ex: politics, economy, demographics)
    - average weight rating past users have give the dataset
    - the range of values the dataset includes (input values(see below) must fall within this range)

If the consuming client submits a request to the 'rankings' endpoint with the correct
(subject to ruthless validation) input fields (for example a preferred value and weight
for each dataset in the database) the API will calculate the score for each country 
based on the input provided and will return a list of all countries ranked/scored.
These requests will be POST requests, and the results will be cached by the API to speed up future requests with the same/similar input field values.
- a list of datasets (datasets can be excluded, will be treated as if they had a 0 weight)
    - standard input format:
        - each dataset includes an 'optimal value' which is a value within the bounds of the dataset's data (not less than or more than the min/max countrys' values) and which is 
        used to calculate a score for each country based on how close they are to the optimal value within the distribution of all countries
        - each dataset also includes a weight value, if this is zero then the dataset is included from the returned rankings, otherwise the rankings for that dataset are multiplied by its weight.
    - custom calculation input format:
        -alternatively, if the consuming client wants to implement their own ranking calculation a  mathematical formula (safe from code-injection attacks) can be provided which accepts a country's data value (within the bounds of that dataset) as an argument and returns a score (score's returned should conform to the API's score range specification... ex: 1000 is a perfect score and 0 is the worst possible score).


## Data Input

The app will also serve a data-input UI, requiring login and admin access.
This UI facilitates rapid entry of new datasets into the database, as well as 
any other CRUD operations on existing datasets.

Data input can also be achieved via an API call to 'datasets' with POST. If the request is properly 
validated and formatted it will be accepted as a new dataset. 

### POST to 'datasets':
- request must have been made by currently logged in valid ADMIN user account
- field must include every country in the country master list as well as a meta field
- must have a valid data type, included in the 'allowed data types' list
- must have a valid source link, and all other meta fields
- if sent with no dataset ID a new dataset will be created
- if sent with a dataset ID the dataset must exist in the database, if it does the old 
 data will be overwritten

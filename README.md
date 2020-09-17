# Test task

## Part A: Building a product import script

### The solution is based on Docker with the following components
 - nginx
 - php7.3
 - mysql:5.7 
 
### Installation 
#### To install the project please follow the instructions below 
 - clone the project
 - open the project folder in CLI and run the command
```
# Docker images are created. Docker containers runnig 
docker-compose up -d
```  
 - run the command
```
# Show the running containers
docker ps
```    
 - go into the "php-fpm" container by id
```
# Show the running containers
docker exec -it {CONTAINER ID} bash
```
 - install the symfony and database  
```
// symfony
composer install

// Product table
php bin/console doctrine:migration:migrate
```

#### Docker start/stop commands

- start
```
docker-compose up -d
```  
 - stop
```
docker-compose stop
```    

### Test
#### The project contains unit tests to test data validation and data saving
#### To install the project please follow the instructions below 
 - start the docker
 - go into the "php-fpm" container
 - run the command
```
php bin/phpunit tests 
```  

### Command execution
#### To run the command follow the instructions below
 - start the docker
 - copy csv file into the **www** folder 
 - go into the "php-fpm" container
 - run the command with appropriate verbosity level (-vvv)
```
# shows only errors
php bin/console app:product:import --source=filename -v

# shows errors and general information 
php bin/console app:product:import --source=filename -vv

# shows errors, debug information, general information 
php bin/console app:product:import --source=filename -vvv 
```  
 
## Part B: Designing a more scalable solution

 - The csv files should be in one folder. 
   The names are numerical, the newest file has the biggest number in the name.
 - The command is run by cron.
   The command uses a storage to save the filename and status of the current task.
     - status 'in progress'
       The command is not executed. 
       In other case the product data can be outdated if there are the same products in more than one file.
     - status 'done'
       The command is executed. The file with the name grater than filename from storage is selected.
 - The services should be created to communicate with third party systems
   The Guzzle library can be used to build such services (https://github.com/guzzle/guzzle)          
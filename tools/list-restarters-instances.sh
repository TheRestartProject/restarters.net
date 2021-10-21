#!/bin/bash
YELLOW='\033[1;33m'
CYAN='\033[1;36m'
RESET='\033[0m'

print_info () {
        echo -e "\t${CYAN}$1${RESET}:         \t$2"
}

for instance in restarters.dev*; do
	cd $instance
	BRANCH=`git status | grep 'On branch' | sed 's/On branch //'`
	APP_ENV=`grep APP_ENV .env | sed 's/APP_ENV=//'`
	DB=`grep DB_DATABASE .env | sed 's/DB_DATABASE=//'`
        echo -e "${YELLOW}$instance${RESET}"
	print_info "Branch" $BRANCH
	print_info "APP_ENV" $APP_ENV
	print_info "DB_DATABASE" $DB
	echo
	cd ..
done

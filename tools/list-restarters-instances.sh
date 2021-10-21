#!/bin/bash
for instance in restarters.dev*; do
	cd $instance
	echo $instance `git status | grep 'On branch'`
	cd ..
done

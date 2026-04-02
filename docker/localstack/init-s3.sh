#!/bin/bash
echo "Creating S3 bucket..."
awslocal s3 mb s3://workshop-uploads
echo "S3 bucket created."

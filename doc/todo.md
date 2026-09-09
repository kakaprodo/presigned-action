List of improvement: v1.0.1

- The facade comment is wrong and need to be improved: method are defined without arguments yet the have
- on the access key model , we need a method to revalidateAccisible(Model $accessible), and revalidateWhoami($whoami)
- Support optional column `origin` when generating the access key. also this value should be verified when checking the access key uniqueness
- let's support the column `permissions` as well. it's a nullable json column
- in the GenerateTemporaryAccessKeyAction, call a function to $data->accessKeyIsGeneratedWithSameValues(?TemporaryAccessKey $existingAccessKey), this will validate if scopes, permissions, and origin are the same, if they are the same that's when you can: - return the existing accesskEY OF IT is not yet expired - allow the update of the exising accessKey, otherwise generate a new one.

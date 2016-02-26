require 'bundler/setup'

require 'rubocop/rake_task'
RuboCop::RakeTask.new(:rubocop) do |task|
  # if you use mediawiki-vagrant, rubocop will by default use it's .rubocop.yml
  # the next line makes it explicit that you want .rubocop.yml from the directory
  # where `bundle exec rake` is executed
  task.options = ['-c', '.rubocop.yml']
end

desc 'beta_chrome'
task :beta_chrome do
  Dir.chdir('tests/browser') do
    sh 'bundle exec cucumber --backtrace --color --verbose --format pretty --format'\
       'Cucumber::Formatter::Sauce --out /mnt/jenkins-workspace/workspace/'\
       'browsertests-Math-en.wikipedia.beta.wmflabs.org-linux-chrome-sauce/log/junit --tags '\
       '@en.wikipedia.beta.wmflabs.org --tags @chrome'
  end
end

task default: [:test]

desc 'Run all build/tests commands (CI entry point)'
task test: [:rubocop]

require 'bundler/setup'

require 'rubocop/rake_task'
RuboCop::RakeTask.new(:rubocop) do |task|
  # if you use mediawiki-vagrant, rubocop will by default use it's .rubocop.yml
  # the next line makes it explicit that you want .rubocop.yml from the directory
  # where `bundle exec rake` is executed
  task.options = ['-c', '.rubocop.yml']
end

require 'mediawiki_selenium/rake_task'
module MediawikiSelenium
  class RakeTask < Cucumber::Rake::Task
    def initialize(name: :selenium, test_dir: Environment.default_test_directory, site_tag: true)
      target = File.expand_path(test_dir, Rake.original_dir)
      env = Environment.load_default(target)

      workspace = env.lookup(:workspace, default: nil)
      site = URI.parse(env.lookup(:mediawiki_url)).host
      browser_tags = env.browser_tags.map { |tag| "@#{tag}" }.join(',')

      require 'shellwords'
      options = Shellwords.escape(test_dir)

      if workspace
        options +=
          ' --backtrace --verbose --color --format pretty'\
          " --format Cucumber::Formatter::Sauce --out '#{workspace}/log/junit'"\
          ' --format rerun --out .cucumber.rerun'\
          ' --tags ~@skip'
        options +=
          " --tags @#{site}" if site_tag
      end

      super(name) do |t|
        t.cucumber_opts = "#{options} --tags #{browser_tags}"
      end
    end
  end
end

MediawikiSelenium::RakeTask.new

namespace :selenium do
  Cucumber::Rake::Task.new(:rerun, 'Re-run failed Cucumber features') do |t|
    t.cucumber_opts = `cat .cucumber.rerun`
  end
end

task default: [:test]

desc 'Run all build/tests commands (CI entry point)'
task test: [:rubocop]
